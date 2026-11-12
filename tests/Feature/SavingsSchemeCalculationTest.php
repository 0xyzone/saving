<?php

namespace Tests\Feature;

use App\Models\SavingsScheme;
use App\Models\SchemeInstallment;
use App\Models\User;
use App\Services\SavingsSchemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsSchemeCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected SavingsSchemeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SavingsSchemeService;
    }

    public function test_scheme_initialization_creates_seats_and_periods(): void
    {
        $organizer = User::create([
            'name' => 'Organizer User',
            'primary_contact_number' => '9801000001',
            'password' => 'secret',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Test 20-Period Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 20,
            'total_periods' => 20,
            'installment_amount' => 10000,
            'interest_amount' => 500,
            'khaja_kharcha' => 1000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->service->initializeScheme($scheme);

        $this->assertCount(20, $scheme->seats);
        $this->assertCount(20, $scheme->periods);

        $seat1 = $scheme->seats()->where('seat_number', 1)->first();
        $this->assertEquals($organizer->id, $seat1->user_id);
        $this->assertEquals('assigned', $seat1->status);

        $seat2 = $scheme->seats()->where('seat_number', 2)->first();
        $this->assertNull($seat2->user_id);
        $this->assertEquals('available', $seat2->status);
    }

    public function test_period_1_organizer_wins_in_full_without_khaja_deduction(): void
    {
        $organizer = User::create([
            'name' => 'Organizer',
            'primary_contact_number' => '9801000001',
            'password' => 'secret',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Test Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 20,
            'total_periods' => 20,
            'installment_amount' => 10000,
            'interest_amount' => 500,
            'khaja_kharcha' => 1000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->service->initializeScheme($scheme);

        // Assign seats 2..20 to members
        for ($i = 2; $i <= 20; $i++) {
            $member = User::create([
                'name' => "Member {$i}",
                'primary_contact_number' => "980100000{$i}",
                'password' => 'secret',
            ]);
            $this->service->assignSeat($scheme, $i, $member);
        }

        $period1 = $scheme->periods()->where('period_number', 1)->first();
        $seat1 = $scheme->seats()->where('seat_number', 1)->first();

        // Calculate preview
        $preview = $this->service->calculateWinnerPayoutPreview($scheme, $period1, $seat1);

        // Gross pool = 20 * 10,000 = 200,000
        $this->assertEquals(200000.00, $preview['gross_pool']);
        // Winner dues = 10,000 (Organizer's seat 1)
        $this->assertEquals(10000.00, $preview['winner_dues']);
        // Khaja kharcha = 0 (no deduction for period 1)
        $this->assertEquals(0.00, $preview['khaja_kharcha']);
        // Net payout = 200,000 - 10,000 - 0 = 190,000
        $this->assertEquals(190000.00, $preview['net_payout']);

        // Declare winner
        $this->service->declareWinner($scheme, $period1, $seat1);

        $period1->refresh();
        $this->assertEquals(190000.00, $period1->net_payout_amount);
        $this->assertEquals('completed', $period1->status);

        // Check that organizer's installment is auto-settled
        $organizerInstallment = SchemeInstallment::where('scheme_period_id', $period1->id)
            ->where('scheme_seat_id', $seat1->id)
            ->first();
        $this->assertEquals('auto_settled_winner', $organizerInstallment->status);
    }

    public function test_period_2_deducts_khaja_kharcha_and_winner_dues(): void
    {
        $organizer = User::create([
            'name' => 'Organizer',
            'primary_contact_number' => '9801000001',
            'password' => 'secret',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Test Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 20,
            'total_periods' => 20,
            'installment_amount' => 10000,
            'interest_amount' => 500,
            'khaja_kharcha' => 1000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->service->initializeScheme($scheme);

        $members = [];
        for ($i = 2; $i <= 20; $i++) {
            $members[$i] = User::create([
                'name' => "Member {$i}",
                'primary_contact_number' => "980100000{$i}",
                'password' => 'secret',
            ]);
            $this->service->assignSeat($scheme, $i, $members[$i]);
        }

        // Complete period 1
        $period1 = $scheme->periods()->where('period_number', 1)->first();
        $seat1 = $scheme->seats()->where('seat_number', 1)->first();
        $this->service->declareWinner($scheme, $period1, $seat1);

        // Period 2: Member 2 on Seat 2 wins
        $period2 = $scheme->periods()->where('period_number', 2)->first();
        $seat2 = $scheme->seats()->where('seat_number', 2)->first();

        $preview = $this->service->calculateWinnerPayoutPreview($scheme, $period2, $seat2);

        // Gross pool = 20 * 10,000 = 200,000 (Organizer pays no interest)
        $this->assertEquals(200000.00, $preview['gross_pool']);
        // Winner dues = 10,000
        $this->assertEquals(10000.00, $preview['winner_dues']);
        // Khaja kharcha = 1,000
        $this->assertEquals(1000.00, $preview['khaja_kharcha']);
        // Net payout = 200,000 - 10,000 - 1,000 = 189,000
        $this->assertEquals(189000.00, $preview['net_payout']);

        $this->service->declareWinner($scheme, $period2, $seat2);
        $period2->refresh();
        $this->assertEquals(189000.00, $period2->net_payout_amount);
    }

    public function test_period_3_includes_interest_from_past_winner(): void
    {
        $organizer = User::create([
            'name' => 'Organizer',
            'primary_contact_number' => '9801000001',
            'password' => 'secret',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Test Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 20,
            'total_periods' => 20,
            'installment_amount' => 10000,
            'interest_amount' => 500,
            'khaja_kharcha' => 1000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->service->initializeScheme($scheme);

        $members = [];
        for ($i = 2; $i <= 20; $i++) {
            $members[$i] = User::create([
                'name' => "Member {$i}",
                'primary_contact_number' => "980100000{$i}",
                'password' => 'secret',
            ]);
            $this->service->assignSeat($scheme, $i, $members[$i]);
        }

        // Period 1
        $this->service->declareWinner(
            $scheme,
            $scheme->periods()->where('period_number', 1)->first(),
            $scheme->seats()->where('seat_number', 1)->first()
        );

        // Period 2: Seat 2 wins
        $this->service->declareWinner(
            $scheme,
            $scheme->periods()->where('period_number', 2)->first(),
            $scheme->seats()->where('seat_number', 2)->first()
        );

        // Period 3: Seat 3 wins
        $period3 = $scheme->periods()->where('period_number', 3)->first();
        $seat3 = $scheme->seats()->where('seat_number', 3)->first();

        // In Period 3:
        // Seat 1 (Organizer): base 10000, interest 0
        // Seat 2 (Won in Period 2): base 10000, interest 500 = 10500
        // Seats 3..20 (Not won yet): base 10000 each = 180,000
        // Gross pool = 10000 + 10500 + 180000 = 200,500
        // Winner dues (Seat 3) = 10000
        // Khaja kharcha = 1000
        // Net payout = 200,500 - 10000 - 1000 = 189,500
        $preview = $this->service->calculateWinnerPayoutPreview($scheme, $period3, $seat3);

        $this->assertEquals(200500.00, $preview['gross_pool']);
        $this->assertEquals(10000.00, $preview['winner_dues']);
        $this->assertEquals(1000.00, $preview['khaja_kharcha']);
        $this->assertEquals(189500.00, $preview['net_payout']);

        $this->service->declareWinner($scheme, $period3, $seat3);
        $period3->refresh();
        $this->assertEquals(189500.00, $period3->net_payout_amount);
    }

    public function test_multi_seat_winner_has_all_seat_dues_deducted(): void
    {
        $organizer = User::create([
            'name' => 'Organizer',
            'primary_contact_number' => '9801000001',
            'password' => 'secret',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Test Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 5,
            'total_periods' => 5,
            'installment_amount' => 10000,
            'interest_amount' => 500,
            'khaja_kharcha' => 1000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->service->initializeScheme($scheme);

        // Member A owns Seat 2 AND Seat 3
        $memberA = User::create([
            'name' => 'Multi-Seat Member A',
            'primary_contact_number' => '9801000002',
            'password' => 'secret',
        ]);
        $this->service->assignSeat($scheme, 2, $memberA);
        $this->service->assignSeat($scheme, 3, $memberA);

        $memberB = User::create([
            'name' => 'Member B',
            'primary_contact_number' => '9801000004',
            'password' => 'secret',
        ]);
        $this->service->assignSeat($scheme, 4, $memberB);

        $memberC = User::create([
            'name' => 'Member C',
            'primary_contact_number' => '9801000005',
            'password' => 'secret',
        ]);
        $this->service->assignSeat($scheme, 5, $memberC);

        // Complete period 1
        $this->service->declareWinner(
            $scheme,
            $scheme->periods()->where('period_number', 1)->first(),
            $scheme->seats()->where('seat_number', 1)->first()
        );

        // Period 2: Member A wins on Seat 2
        $period2 = $scheme->periods()->where('period_number', 2)->first();
        $seat2 = $scheme->seats()->where('seat_number', 2)->first();

        // Gross pool = 5 * 10,000 = 50,000
        // Member A owns Seat 2 (10,000) and Seat 3 (10,000) = 20,000 dues!
        // Khaja kharcha = 1,000
        // Net payout = 50,000 - 20,000 - 1,000 = 29,000
        $preview = $this->service->calculateWinnerPayoutPreview($scheme, $period2, $seat2);
        $this->assertEquals(50000.00, $preview['gross_pool']);
        $this->assertEquals(20000.00, $preview['winner_dues']);
        $this->assertEquals(1000.00, $preview['khaja_kharcha']);
        $this->assertEquals(29000.00, $preview['net_payout']);

        $this->service->declareWinner($scheme, $period2, $seat2);

        // Verify both Seat 2 and Seat 3 installments are marked auto_settled_winner
        $seat2Installment = SchemeInstallment::where('scheme_period_id', $period2->id)
            ->where('scheme_seat_id', 2)
            ->first();
        $seat3Installment = SchemeInstallment::where('scheme_period_id', $period2->id)
            ->where('scheme_seat_id', 3)
            ->first();

        $this->assertEquals('auto_settled_winner', $seat2Installment->status);
        $this->assertEquals('auto_settled_winner', $seat3Installment->status);
    }
}
