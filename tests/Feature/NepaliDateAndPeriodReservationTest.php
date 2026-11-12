<?php

namespace Tests\Feature;

use App\Filament\Resources\SavingsSchemes\Pages\ManageSavingsScheme;
use App\Models\SavingsScheme;
use App\Models\SchemePeriodReservation;
use App\Models\User;
use App\Services\NepaliDateService;
use App\Services\SavingsSchemeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NepaliDateAndPeriodReservationTest extends TestCase
{
    use RefreshDatabase;

    protected SavingsSchemeService $schemeService;

    protected NepaliDateService $nepaliDateService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schemeService = new SavingsSchemeService;
        $this->nepaliDateService = new NepaliDateService;
    }

    public function test_nepali_date_conversions_and_month_additions(): void
    {
        $adDate = Carbon::parse('2024-04-13');
        $bsDate = $this->nepaliDateService->toBs($adDate);
        $this->assertNotEmpty($bsDate);

        // Converting back to AD returns YYYY-MM-DD string
        $convertedAd = $this->nepaliDateService->toAd($bsDate);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $convertedAd);

        // Advance nepali months
        $nextMonthBs = $this->nepaliDateService->addNepaliMonths('2081-01-15', 1);
        $this->assertEquals('2081-02-15', $nextMonthBs);

        // Day capping for shorter months
        $cappedBs = $this->nepaliDateService->addNepaliMonths('2081-01-31', 1);
        $this->assertNotNull($cappedBs);
    }

    public function test_scheme_creation_populates_nepali_dates_for_periods(): void
    {
        $organizer = User::create([
            'name' => 'Organizer',
            'primary_contact_number' => '9801000001',
            'password' => 'secret',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Nepali Date Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 5,
            'total_periods' => 5,
            'installment_amount' => 5000,
            'interest_amount' => 200,
            'khaja_kharcha' => 500,
            'frequency' => 'monthly',
            'start_date' => '2024-04-14',
            'status' => 'active',
        ]);

        $this->schemeService->initializeScheme($scheme);

        $scheme->refresh();
        $this->assertNotEmpty($scheme->start_date_bs);

        $periods = $scheme->periods()->orderBy('period_number')->get();
        $this->assertCount(5, $periods);

        foreach ($periods as $period) {
            $this->assertNotEmpty($period->due_date_bs);
            $this->assertNotEmpty($period->formatted_due_date_bs);
        }
    }

    public function test_period_reservation_workflow(): void
    {
        $organizer = User::create([
            'name' => 'Organizer User',
            'primary_contact_number' => '9801000001',
            'password' => 'secret',
        ]);

        $member = User::create([
            'name' => 'Member User',
            'primary_contact_number' => '9801000002',
            'password' => 'secret',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Reservation Test Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 4,
            'total_periods' => 4,
            'installment_amount' => 5000,
            'interest_amount' => 200,
            'khaja_kharcha' => 500,
            'frequency' => 'monthly',
            'start_date' => '2024-04-14',
            'status' => 'active',
        ]);

        $this->schemeService->initializeScheme($scheme);

        $this->schemeService->assignSeat($scheme, 2, $member);
        $seat2 = $scheme->seats()->where('seat_number', 2)->first();

        $period3 = $scheme->periods()->where('period_number', 3)->first();

        // Apply reservation
        $reservation = $this->schemeService->applyPeriodReservation(
            $scheme,
            $period3,
            $member,
            $seat2,
            'Need funds for festive season',
            $organizer
        );

        $this->assertInstanceOf(SchemePeriodReservation::class, $reservation);
        $this->assertEquals('pending', $reservation->status);
        $this->assertEquals($member->id, $reservation->user_id);
        $this->assertEquals($seat2->id, $reservation->scheme_seat_id);

        // Approve reservation
        $this->schemeService->approvePeriodReservation($reservation, $organizer);
        $reservation->refresh();
        $this->assertEquals('approved', $reservation->status);
        $this->assertNotNull($reservation->reviewed_at);

        $period3->refresh();
        $this->assertNotNull($period3->approvedReservation);
        $this->assertEquals($reservation->id, $period3->approvedReservation->id);
    }

    public function test_declare_winner_records_payout_details_and_calculates_balance(): void
    {
        $organizer = User::create([
            'name' => 'Organizer',
            'primary_contact_number' => '9801000001',
            'password' => 'secret',
        ]);

        $member = User::create([
            'name' => 'Winner Member',
            'primary_contact_number' => '9801000002',
            'password' => 'secret',
        ]);

        $member3 = User::create([
            'name' => 'Member Three',
            'primary_contact_number' => '9801000003',
            'password' => 'secret',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Payout Settlement Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 3,
            'total_periods' => 3,
            'installment_amount' => 10000,
            'interest_amount' => 500,
            'khaja_kharcha' => 1000,
            'frequency' => 'monthly',
            'start_date' => '2024-04-14',
            'status' => 'active',
        ]);

        $this->schemeService->initializeScheme($scheme);

        $this->schemeService->assignSeat($scheme, 2, $member);
        $this->schemeService->assignSeat($scheme, 3, $member3);
        $seat2 = $scheme->seats()->where('seat_number', 2)->first();

        // Period 1 is won by organizer
        $period1 = $scheme->periods()->where('period_number', 1)->first();
        $seat1 = $scheme->seats()->where('seat_number', 1)->first();
        $this->schemeService->declareWinner($scheme, $period1, $seat1);

        // Declare Period 2 winner with eSewa payout method and partial payment
        $period2 = $scheme->periods()->where('period_number', 2)->first();
        $preview = $this->schemeService->calculateWinnerPayoutPreview($scheme, $period2, $seat2);

        $this->schemeService->declareWinner(
            $scheme,
            $period2,
            $seat2,
            'Notes for payout',
            'eSewa',
            'ESEWA-TXN-984123',
            15000 // Partial paid amount
        );

        $period2->refresh();
        $this->assertEquals('eSewa', $period2->payout_method);
        $this->assertEquals('ESEWA-TXN-984123', $period2->payout_reference);
        $this->assertEquals(15000, (float) $period2->paid_amount);
        $this->assertEquals($preview['net_payout'] - 15000, $period2->winner_balance_remaining);

        // Settle remaining balance
        $remaining = $period2->winner_balance_remaining;
        $this->schemeService->settleWinnerPayout(
            $period2,
            $remaining,
            'Bank Transfer',
            'NABIL-RTGS-5544'
        );

        $period2->refresh();
        $this->assertEquals(0, $period2->winner_balance_remaining);
        $this->assertEquals($preview['net_payout'], (float) $period2->paid_amount);
    }

    public function test_manage_savings_scheme_livewire_round_modal_and_settlement(): void
    {
        $role = Role::create(['name' => 'super_admin']);
        $user = User::create([
            'name' => 'Super Admin User',
            'primary_contact_number' => '9801000000',
            'password' => 'secret',
        ]);
        $user->assignRole($role);

        $scheme = SavingsScheme::create([
            'title' => 'Livewire Management Scheme',
            'organizer_id' => $user->id,
            'total_seats' => 3,
            'total_periods' => 3,
            'installment_amount' => 5000,
            'interest_amount' => 200,
            'khaja_kharcha' => 500,
            'frequency' => 'monthly',
            'start_date' => '2024-04-14',
            'status' => 'active',
        ]);

        $this->schemeService->initializeScheme($scheme);

        $this->actingAs($user);

        $period1 = $scheme->periods()->where('period_number', 1)->first();
        $installment = $period1->installments()->first();

        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme->id])
            ->assertSuccessful()
            ->call('openPeriodDetails', $period1->id)
            ->assertSet('viewPeriodId', $period1->id)
            ->call('closePeriodDetails')
            ->assertSet('viewPeriodId', null)
            ->call('promptMarkInstallmentPaid', $installment->id)
            ->assertSet('selectedInstallmentId', $installment->id)
            ->assertSet('installmentPaymentMethod', 'Cash')
            ->set('installmentPaymentMethod', 'Khalti')
            ->set('installmentPaymentReference', 'KHALTI-1234')
            ->call('confirmMarkInstallmentPaid')
            ->assertSet('selectedInstallmentId', null);

        $installment->refresh();
        $this->assertEquals('paid', $installment->status);
        $this->assertEquals('Khalti', $installment->payment_method);
        $this->assertStringContainsString('KHALTI-1234', (string) $installment->notes);

        // Test Round Validation Modal workflow
        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme->id])
            ->assertSuccessful()
            ->call('openValidateRound', $period1->id)
            ->assertSet('validatingPeriodId', $period1->id)
            ->set('validationNotes', 'All accounts verified by committee auditor')
            ->call('confirmValidateRound')
            ->assertSet('validatingPeriodId', null);

        $period1->refresh();
        $this->assertTrue($period1->is_validated);
        $this->assertEquals($user->id, $period1->validated_by);
        $this->assertEquals('All accounts verified by committee auditor', $period1->validation_notes);

        // Test Revoking Validation
        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme->id])
            ->call('toggleRevokeValidation', $period1->id);

        $period1->refresh();
        $this->assertFalse($period1->is_validated);

        // Test Direct Reserve Round Modal workflow
        $period2 = $scheme->periods()->where('period_number', 2)->first();
        $seat2 = $scheme->seats()->where('seat_number', 2)->first();

        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme->id])
            ->call('openReserveModal', $period2->id)
            ->assertSet('reservingPeriodId', $period2->id)
            ->set('reservingSeatId', $seat2->id)
            ->set('reservingReason', 'Urgent family gathering')
            ->set('reservingStatus', 'approved')
            ->call('confirmReserve')
            ->assertSet('reservingPeriodId', null);

        $period2->refresh();
        $this->assertNotNull($period2->approvedReservation);
        $this->assertEquals($seat2->id, $period2->approvedReservation->scheme_seat_id);

        // Test Edit Scheme header action
        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme->id])
            ->callAction('edit_scheme', [
                'title' => 'Updated Committee Scheme Title',
                'organizer_id' => $user->id,
                'status' => 'active',
                'interest_amount' => 350,
                'khaja_kharcha' => 750,
                'description' => 'Updated committee description',
            ])
            ->assertHasNoActionErrors();

        $scheme->refresh();
        $this->assertEquals('Updated Committee Scheme Title', $scheme->title);
        $this->assertEquals(350, (float) $scheme->interest_amount);
        $this->assertEquals(750, (float) $scheme->khaja_kharcha);
    }

    public function test_nepali_date_dropdown_helpers_and_period_due_date_calculation(): void
    {
        $years = NepaliDateService::getNepaliYears();
        $this->assertNotEmpty($years);

        $months = NepaliDateService::getNepaliMonths();
        $this->assertCount(12, $months);

        $days = NepaliDateService::getNepaliDays(1, 2083);
        $this->assertNotEmpty($days);

        [$y, $m, $d] = NepaliDateService::explodeBs('2083-08-15');
        $this->assertEquals(2083, $y);
        $this->assertEquals(8, $m);
        $this->assertEquals(15, $d);

        $user = User::create([
            'name' => 'Nepali Organizer',
            'primary_contact_number' => '9801122334',
            'password' => 'secret',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Nepali Calendar Scheme',
            'organizer_id' => $user->id,
            'total_seats' => 4,
            'total_periods' => 4,
            'installment_amount' => 5000,
            'interest_amount' => 200,
            'khaja_kharcha' => 500,
            'frequency' => 'monthly',
            'start_date_bs' => '2083-08-15',
            'status' => 'active',
        ]);

        $this->schemeService->initializeScheme($scheme);

        $scheme->refresh();
        $this->assertNotNull($scheme->start_date);
        $this->assertEquals(NepaliDateService::toAd('2083-08-15'), $scheme->start_date->format('Y-m-d'));

        $periods = $scheme->periods()->orderBy('period_number')->get();
        $this->assertCount(4, $periods);

        // Due dates should strictly follow Nepali months
        $this->assertEquals('2083-08-15', $periods[0]->due_date_bs);
        $this->assertEquals('2083-09-15', $periods[1]->due_date_bs);
        $this->assertEquals('2083-10-15', $periods[2]->due_date_bs);
        $this->assertEquals('2083-11-15', $periods[3]->due_date_bs);

        // Gregorian dates must match the respective Nepali due dates
        foreach ($periods as $period) {
            $expectedAd = NepaliDateService::toAd($period->due_date_bs);
            $this->assertEquals($expectedAd, $period->due_date->format('Y-m-d'));
        }
    }
}
