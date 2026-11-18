<?php

namespace Tests\Feature;

use App\Filament\Resources\SavingsSchemes\SavingsSchemeResource;
use App\Filament\Widgets\MemberSchemesOverview;
use App\Filament\Widgets\MyInstallmentHistory;
use App\Filament\Widgets\MyOngoingSchemeSeats;
use App\Filament\Widgets\MyReservationsAndWinnings;
use App\Filament\Widgets\PotentialReturnCalculator;
use App\Filament\Widgets\UsersOverview;
use App\Models\SavingsScheme;
use App\Models\SchemePeriodReservation;
use App\Models\User;
use App\Models\UserPaymentOption;
use App\Services\SavingsSchemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_overview_widget_shows_total_users_only_to_super_admin_with_dynamic_columns(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => 'Super Admin',
            'primary_contact_number' => '9800000001',
            'password' => 'secret123',
        ]);
        $admin->assignRole($superAdminRole);

        $regular = User::create([
            'name' => 'Regular User',
            'primary_contact_number' => '9800000002',
            'password' => 'secret123',
        ]);

        $this->actingAs($admin);
        Livewire::test(UsersOverview::class)
            ->assertSuccessful()
            ->assertSee('Total Users')
            ->assertSee('Active Payment Methods')
            ->assertSee('Preferred Payment Methods');

        $this->actingAs($regular);
        Livewire::test(UsersOverview::class)
            ->assertSuccessful()
            ->assertDontSee('Total Users')
            ->assertSee('Active Payment Methods')
            ->assertSee('Preferred Payment Methods');
    }

    public function test_member_widgets_are_hidden_for_users_without_ongoing_scheme_seats(): void
    {
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        $organizer = User::create([
            'name' => 'Organizer',
            'primary_contact_number' => '9800000001',
            'password' => 'secret123',
        ]);

        $member = User::create([
            'name' => 'Member Without Seats',
            'primary_contact_number' => '9800000002',
            'password' => 'secret123',
        ]);

        $completedScheme = SavingsScheme::create([
            'title' => 'Completed Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 3,
            'total_periods' => 3,
            'installment_amount' => 5000,
            'interest_amount' => 100,
            'khaja_kharcha' => 200,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'completed',
        ]);

        $service = app(SavingsSchemeService::class);
        $service->initializeScheme($completedScheme);
        $service->assignSeat($completedScheme, 2, $member);

        $this->actingAs($member);

        $this->assertFalse(MemberSchemesOverview::canView());
        $this->assertFalse(MyOngoingSchemeSeats::canView());
        $this->assertFalse(MyInstallmentHistory::canView());
        $this->assertFalse(MyReservationsAndWinnings::canView());
    }

    public function test_member_widgets_render_ongoing_seats_installments_winnings_and_reservations(): void
    {
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        $organizer = User::create([
            'name' => 'Organizer',
            'primary_contact_number' => '9800000001',
            'password' => 'secret123',
        ]);

        $member = User::create([
            'name' => 'Active Member',
            'primary_contact_number' => '9800000002',
            'password' => 'secret123',
        ]);

        $otherMember = User::create([
            'name' => 'Other Member',
            'primary_contact_number' => '9800000003',
            'password' => 'secret123',
        ]);

        $activeScheme = SavingsScheme::create([
            'title' => 'Gold Active Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 3,
            'total_periods' => 3,
            'installment_amount' => 10000,
            'interest_amount' => 500,
            'khaja_kharcha' => 1000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $service = app(SavingsSchemeService::class);
        $service->initializeScheme($activeScheme);
        $memberSeat = $service->assignSeat($activeScheme, 2, $member);
        $otherSeat = $service->assignSeat($activeScheme, 3, $otherMember);

        $period1 = $activeScheme->periods()->where('period_number', 1)->first();
        $service->generatePeriodInstallments($activeScheme, $period1);

        $memberInstallment = $period1->installments()->where('scheme_seat_id', $memberSeat->id)->first();
        $otherInstallment = $period1->installments()->where('scheme_seat_id', $otherSeat->id)->first();

        $period2 = $activeScheme->periods()->where('period_number', 2)->first();
        SchemePeriodReservation::create([
            'savings_scheme_id' => $activeScheme->id,
            'scheme_period_id' => $period2->id,
            'scheme_seat_id' => $memberSeat->id,
            'user_id' => $member->id,
            'requested_by_user_id' => $member->id,
            'reason' => 'Need funds for family event',
            'status' => 'pending',
        ]);

        $this->actingAs($member);

        $this->assertTrue(MemberSchemesOverview::canView());
        $this->assertTrue(MyOngoingSchemeSeats::canView());
        $this->assertTrue(MyInstallmentHistory::canView());
        $this->assertTrue(MyReservationsAndWinnings::canView());

        Livewire::test(MemberSchemesOverview::class)
            ->assertSuccessful()
            ->assertSee('My Active Seats')
            ->assertSee('Current Period Dues')
            ->assertSee('10,000.00');

        Livewire::test(MyOngoingSchemeSeats::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$memberSeat])
            ->assertCanNotSeeTableRecords([$otherSeat])
            ->callTableAction('view_details', $memberSeat);

        Livewire::test(MyInstallmentHistory::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$memberInstallment])
            ->assertCanNotSeeTableRecords([$otherInstallment]);

        Livewire::test(MyReservationsAndWinnings::class)
            ->assertSuccessful()
            ->assertSee('Gold Active Scheme')
            ->assertSee('Need funds for family event');
    }

    public function test_payment_options_single_preferred_and_nav_visibility(): void
    {
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        $user = User::create([
            'name' => 'Member One',
            'username' => 'memberone',
            'password' => 'secret123',
        ]);

        $otherUser = User::create([
            'name' => 'Member Two',
            'username' => 'membertwo',
            'password' => 'secret123',
        ]);

        $opt1 = UserPaymentOption::create([
            'user_id' => $user->id,
            'type' => 'wallet',
            'provider_name' => 'eSewa',
            'account_number' => '9800000001',
            'is_preferred' => true,
            'is_active' => true,
        ]);

        $opt2 = UserPaymentOption::create([
            'user_id' => $user->id,
            'type' => 'bank',
            'provider_name' => 'Nabil Bank',
            'account_number' => '0011223344',
            'is_preferred' => true,
            'is_active' => true,
        ]);

        UserPaymentOption::create([
            'user_id' => $otherUser->id,
            'type' => 'wallet',
            'provider_name' => 'Khalti',
            'account_number' => '9811111111',
            'is_preferred' => true,
            'is_active' => true,
        ]);

        $this->assertFalse($opt1->fresh()->is_preferred);
        $this->assertTrue($opt2->fresh()->is_preferred);

        $this->actingAs($user);
        $this->assertFalse(SavingsSchemeResource::shouldRegisterNavigation());

        Livewire::test(UsersOverview::class)
            ->assertSuccessful()
            ->assertSee('2') // 2 active for $user (not 3)
            ->assertSee('1'); // 1 preferred for $user (not 2)
    }

    public function test_potential_return_calculator_visibility_and_multi_seat_projection(): void
    {
        $organizer = User::create([
            'name' => 'Organizer User',
            'email' => 'org-calc@example.com',
            'password' => bcrypt('password'),
        ]);

        $member = User::create([
            'name' => 'Multi Seat Member',
            'email' => 'member-calc@example.com',
            'password' => bcrypt('password'),
        ]);

        $outsider = User::create([
            'name' => 'Outsider User',
            'email' => 'outsider-calc@example.com',
            'password' => bcrypt('password'),
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Calc Test Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 4,
            'total_periods' => 4,
            'installment_amount' => 1000,
            'interest_amount' => 100,
            'khaja_kharcha' => 200,
            'frequency' => 'monthly',
            'start_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $service = app(SavingsSchemeService::class);
        $service->initializeScheme($scheme);

        // Seat 1: organizer (disbursed in Period 1)
        $seat1 = $scheme->seats()->where('seat_number', 1)->firstOrFail();
        $seat1->update([
            'user_id' => $organizer->id,
            'won_at_period' => 1,
        ]);

        // Seat 2 & Seat 3: member (Seat 2 already disbursed in Period 2; Seat 3 still eligible)
        $seat2 = $scheme->seats()->where('seat_number', 2)->firstOrFail();
        $seat2->update([
            'user_id' => $member->id,
            'won_at_period' => 2,
        ]);

        $seat3 = $scheme->seats()->where('seat_number', 3)->firstOrFail();
        $seat3->update([
            'user_id' => $member->id,
            'won_at_period' => null,
        ]);

        // Seat 4: another member (not yet disbursed)
        $seat4 = $scheme->seats()->where('seat_number', 4)->firstOrFail();
        $seat4->update([
            'user_id' => $organizer->id,
            'won_at_period' => null,
        ]);

        $period1 = $scheme->periods()->where('period_number', 1)->firstOrFail();
        $period1->update(['status' => 'completed', 'winning_seat_id' => $seat1->id, 'is_validated' => true]);

        $period2 = $scheme->periods()->where('period_number', 2)->firstOrFail();
        $period2->update(['status' => 'completed', 'winning_seat_id' => $seat2->id, 'is_validated' => false]);

        $period3 = $scheme->periods()->where('period_number', 3)->firstOrFail();
        $period3->update(['status' => 'in_progress']);

        $period4 = $scheme->periods()->where('period_number', 4)->firstOrFail();

        // Outsider cannot view the widget
        $this->actingAs($outsider);
        $this->assertFalse(PotentialReturnCalculator::canView());

        // Member with ongoing seats can view the widget
        $this->actingAs($member);
        $this->assertTrue(PotentialReturnCalculator::canView());

        // Only Round #4 is upcoming & unvalidated & unassigned.
        // In Round #4:
        //   Prior rounds 2 & 3 will each have a non-organizer recipient => 2 seats with +100 interest
        //   Gross Pool = 4 * 1000 + 2 * 100 = 4200
        //   User's own dues in Round #4: Seat 2 (1000 + 100) + Seat 3 (1000) = 2100
        //   Maslanda Deposit = 200
        //   Net Disbursement = 4200 - 2100 - 200 = 1900.00
        Livewire::test(PotentialReturnCalculator::class)
            ->assertSuccessful()
            ->assertSee('Potential Return Estimator')
            ->assertSee('2 Seats')
            ->assertSee('1 Eligible Left')
            ->assertSee('Round #4')
            ->assertDontSee('Round #1')
            ->assertDontSee('Round #2')
            ->assertDontSee('Round #3')
            ->assertSee('1,900.00')
            ->assertSee('4,200.00')
            ->assertSee('2,100.00')
            ->assertSee('200.00');

        // When all user seats are disbursed, eligible_seats_left === 0 and Net Disbursement is 0.00
        $seat3->update(['won_at_period' => 3]);

        Livewire::test(PotentialReturnCalculator::class)
            ->assertSuccessful()
            ->assertSee('All Seats Disbursed')
            ->assertSee('0.00');
    }
}
