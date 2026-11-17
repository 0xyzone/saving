<?php

namespace Tests\Feature;

use App\Filament\Widgets\MemberSchemesOverview;
use App\Filament\Widgets\MyInstallmentHistory;
use App\Filament\Widgets\MyOngoingSchemeSeats;
use App\Filament\Widgets\MyReservationsAndWinnings;
use App\Filament\Widgets\UsersOverview;
use App\Models\SavingsScheme;
use App\Models\SchemePeriodReservation;
use App\Models\User;
use App\Services\SavingsSchemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MemberDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_overview_widget_is_visible_only_to_super_admin(): void
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
        $this->assertTrue(UsersOverview::canView());

        $this->actingAs($regular);
        $this->assertFalse(UsersOverview::canView());
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
}
