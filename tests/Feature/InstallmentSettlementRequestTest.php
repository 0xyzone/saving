<?php

namespace Tests\Feature;

use App\Filament\Resources\SavingsSchemes\Pages\ManageSavingsScheme;
use App\Filament\Widgets\MyOngoingSchemeSeats;
use App\Filament\Widgets\PendingSettlementRequestsWidget;
use App\Models\InstallmentSettlementRequest;
use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use App\Models\User;
use App\Services\NepaliDateService;
use App\Services\SavingsSchemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InstallmentSettlementRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_apply_for_settlement_and_organizer_can_approve_via_widget(): void
    {
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        $organizer = User::create([
            'name' => 'Scheme Organizer',
            'email' => 'organizer-req@example.com',
            'password' => bcrypt('password'),
        ]);

        $member = User::create([
            'name' => 'Member Applicant',
            'email' => 'member-req@example.com',
            'password' => bcrypt('password'),
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Settlement Flow Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 3,
            'total_periods' => 3,
            'installment_amount' => 1000,
            'interest_amount' => 100,
            'khaja_kharcha' => 0,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $service = app(SavingsSchemeService::class);
        $service->initializeScheme($scheme);
        $service->assignSeat($scheme, 1, $organizer);
        $seat2 = $service->assignSeat($scheme, 2, $member);

        // Seat assignment sends a database notification to the member
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $member->id,
        ]);

        $period1 = $scheme->periods()->where('period_number', 1)->firstOrFail();
        $period1->update(['status' => 'in_progress']);
        $service->generatePeriodInstallments($scheme, $period1);

        // Keep only Round #1 installment unpaid for this test
        SchemeInstallment::query()
            ->where('scheme_seat_id', $seat2->id)
            ->where('scheme_period_id', '!=', $period1->id)
            ->delete();

        /** @var SchemeInstallment $installment */
        $installment = SchemeInstallment::query()
            ->where('scheme_period_id', $period1->id)
            ->where('scheme_seat_id', $seat2->id)
            ->firstOrFail();

        // Member submits settlement request from MyOngoingSchemeSeats widget
        $this->actingAs($member);

        Livewire::test(MyOngoingSchemeSeats::class)
            ->assertSuccessful()
            ->assertTableActionVisible('apply_settlement', $seat2)
            ->callTableAction('apply_settlement', $seat2, [
                'scheme_installment_id' => $installment->id,
                'bs_year' => 2083,
                'bs_month' => 8,
                'bs_day' => 3,
                'amount' => 1000,
                'payment_method' => 'eSewa',
                'reference_id' => 'ESW-998877',
                'notes' => 'Paid via eSewa wallet',
            ])
            ->assertNotified('Settlement Application Submitted');

        $request = InstallmentSettlementRequest::query()->firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame('2083-08-03', $request->payment_date_bs);
        $this->assertSame(NepaliDateService::toAd('2083-08-03'), $request->payment_date->format('Y-m-d'));
        $this->assertSame('eSewa', $request->payment_method);
        $this->assertSame('ESW-998877', $request->reference_id);
        $this->assertEquals(1000.00, (float) $request->amount);
        $this->assertSame(2, $organizer->notifications()->count());

        // Once pending, apply_settlement hides, Pending Verification shows, and card badge shows Submitted for Review
        $seat2->refresh();
        Livewire::test(MyOngoingSchemeSeats::class)
            ->assertDontSee('Apply Settlement')
            ->assertSee('Pending Verification')
            ->assertSee('Submitted for Review: Rd #1 (Rs. 1,000.00)');

        // Organizer sees pending request in PendingSettlementRequestsWidget and approves it
        $this->actingAs($organizer);
        $this->assertTrue(PendingSettlementRequestsWidget::canView());

        Livewire::test(PendingSettlementRequestsWidget::class)
            ->assertSuccessful()
            ->assertSee('Member Applicant')
            ->assertSee('1,000.00')
            ->callTableAction('approve', $request, [
                'reviewer_notes' => 'Verified on eSewa',
            ])
            ->assertNotified('Settlement Approved');

        $request->refresh();
        $installment->refresh();

        $this->assertSame('approved', $request->status);
        $this->assertSame($organizer->id, $request->reviewed_by);
        $this->assertSame('paid', $installment->status);
        $this->assertEquals(1000.00, (float) $installment->paid_amount);
        $this->assertSame('eSewa', $installment->payment_method);
        $this->assertSame($organizer->id, $installment->verified_by);
        $this->assertFalse(PendingSettlementRequestsWidget::canView());
        $this->assertGreaterThanOrEqual(2, $member->notifications()->count());

        // Member sees Approved badge on seat card and Nepali BS Paid On date in View Details modal
        $this->actingAs($member);
        Livewire::test(MyOngoingSchemeSeats::class)
            ->assertSee('Last Request: Approved (Rd #1)');

        $modalHtml = view('filament.widgets.seat-details-modal', [
            'seat' => $seat2->fresh(['scheme.organizer', 'scheme.periods', 'installments.period', 'settlementRequests.period', 'settlementRequests.reviewer']),
            'currentPeriod' => $period1,
            'currentInstallment' => $installment,
        ])->render();
        $this->assertStringContainsString(NepaliDateService::formatBs($installment->paid_at), $modalHtml);
    }

    public function test_organizer_can_reject_settlement_request_on_manage_scheme_page(): void
    {
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        $organizer = User::create([
            'name' => 'Scheme Organizer Two',
            'email' => 'organizer-rej@example.com',
            'password' => bcrypt('password'),
        ]);

        $member = User::create([
            'name' => 'Member Reject',
            'email' => 'member-rej@example.com',
            'password' => bcrypt('password'),
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Reject Flow Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 3,
            'total_periods' => 3,
            'installment_amount' => 1000,
            'interest_amount' => 100,
            'khaja_kharcha' => 0,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $service = app(SavingsSchemeService::class);
        $service->initializeScheme($scheme);
        $service->assignSeat($scheme, 1, $organizer);
        $seat2 = $service->assignSeat($scheme, 2, $member);

        // Seat assignment sends a database notification to the member
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $member->id,
        ]);

        $period1 = $scheme->periods()->where('period_number', 1)->firstOrFail();
        $period1->update(['status' => 'in_progress']);
        $service->generatePeriodInstallments($scheme, $period1);

        $installment = SchemeInstallment::query()
            ->where('scheme_period_id', $period1->id)
            ->where('scheme_seat_id', $seat2->id)
            ->firstOrFail();

        $request = $service->submitInstallmentSettlementRequest(
            installment: $installment,
            user: $member,
            amount: 1000,
            paymentMethod: 'Bank Transfer',
            paymentDateBs: '2083-08-03',
            referenceId: 'BANK-123',
        );

        $this->actingAs($organizer);

        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme->id])
            ->assertSee('Settlement Requests')
            ->call('rejectSettlementRequest', $request->id)
            ->assertNotified('Settlement Request Rejected');

        $request->refresh();
        $installment->refresh();

        $this->assertSame('rejected', $request->status);
        $this->assertSame('pending', $installment->status);
        $this->assertEquals(0.00, (float) $installment->paid_amount);

        // Member can apply again after rejection
        $this->actingAs($member);
        Livewire::test(MyOngoingSchemeSeats::class)
            ->assertTableActionVisible('apply_settlement', $seat2);
    }
}
