<?php

namespace Tests\Feature;

use App\Filament\Resources\SavingsSchemes\Pages\ListSavingsSchemes;
use App\Filament\Resources\SavingsSchemes\Pages\ManageSavingsScheme;
use App\Filament\Resources\SavingsSchemes\SavingsSchemeResource;
use App\Models\SavingsScheme;
use App\Models\User;
use App\Services\SavingsSchemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SavingsSchemeResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_savings_schemes_resource(): void
    {
        $role = Role::create(['name' => 'super_admin']);
        $admin = User::create([
            'name' => 'Super Admin',
            'primary_contact_number' => '9802350986',
            'password' => 'secret123',
        ]);
        $admin->assignRole($role);

        $this->actingAs($admin);

        $response = $this->get('/savings-schemes');
        $response->assertStatus(200);
    }

    public function test_manage_scheme_page_renders_and_declares_winner(): void
    {
        $role = Role::create(['name' => 'super_admin']);
        $admin = User::create([
            'name' => 'Super Admin',
            'primary_contact_number' => '9802350986',
            'password' => 'secret123',
        ]);
        $admin->assignRole($role);

        $scheme = SavingsScheme::create([
            'title' => 'Feature Test Scheme',
            'organizer_id' => $admin->id,
            'total_seats' => 5,
            'total_periods' => 5,
            'installment_amount' => 10000,
            'interest_amount' => 500,
            'khaja_kharcha' => 1000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        app(SavingsSchemeService::class)->initializeScheme($scheme);

        for ($i = 2; $i <= 5; $i++) {
            $member = User::create([
                'name' => "Member {$i}",
                'primary_contact_number' => "980000000{$i}",
                'password' => 'secret',
            ]);
            app(SavingsSchemeService::class)->assignSeat($scheme, $i, $member);
        }

        $this->actingAs($admin);

        $response = $this->get("/savings-schemes/{$scheme->id}");
        $response->assertStatus(200);
        $response->assertSeeText('Feature Test Scheme');
        $response->assertSeeText('Installment Per Seat');
        $response->assertSeeText('Periods & Winners');

        // Test Livewire component
        $component = Livewire::test(ManageSavingsScheme::class, ['record' => $scheme]);
        $component->assertSuccessful();

        // Settle Period 1 (Seat #1 - Organizer automatically without needing seat dropdown)
        $component->callAction('declare_winner', [
            'payout_notes' => 'Period 1 organizer payout settled',
        ]);

        $scheme->refresh();
        $period1 = $scheme->periods()->where('period_number', 1)->first();
        $this->assertEquals('completed', $period1->status);
        $this->assertEquals(40000.00, $period1->net_payout_amount); // 5 * 10k - 10k (own seat) - 0 = 40,000
        $this->assertEquals(2, $scheme->current_period);

        // Period 2 is now active: declare winner for Period 2 choosing Seat #2
        $seat2 = $scheme->seats()->where('seat_number', 2)->first();
        $component->callAction('declare_winner', [
            'winning_seat_id' => $seat2->id,
            'payout_notes' => 'Period 2 winner declared',
        ]);

        $scheme->refresh();
        $period2 = $scheme->periods()->where('period_number', 2)->first();
        $this->assertEquals('completed', $period2->status);
        // Period 2 payout: 5 * 10,000 = 50,000 gross. Minus seat 2 (10,000). Minus khaja kharcha (1,000) = 39,000.
        $this->assertEquals(39000.00, $period2->net_payout_amount);
        $this->assertEquals(3, $scheme->current_period);
    }

    public function test_non_organizer_cannot_access_savings_schemes(): void
    {
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        $organizer = User::create([
            'name' => 'Organizer A',
            'primary_contact_number' => '9800000002',
            'password' => 'secret123',
        ]);

        $regularUser = User::create([
            'name' => 'Regular Member',
            'primary_contact_number' => '9800000004',
            'password' => 'secret123',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Organizer A Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 5,
            'total_periods' => 5,
            'installment_amount' => 5000,
            'interest_amount' => 200,
            'khaja_kharcha' => 500,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->actingAs($regularUser);
        $this->assertFalse(SavingsSchemeResource::canViewAny());
        $this->get('/savings-schemes')->assertStatus(403);
        $this->get("/savings-schemes/{$scheme->id}")->assertStatus(404);
    }

    public function test_organizer_sees_only_their_own_schemes_and_super_admin_sees_all(): void
    {
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $admin = User::create([
            'name' => 'Super Admin',
            'primary_contact_number' => '9800000001',
            'password' => 'secret123',
        ]);
        $admin->assignRole($superAdminRole);

        $organizerA = User::create([
            'name' => 'Organizer A',
            'primary_contact_number' => '9800000002',
            'password' => 'secret123',
        ]);

        $organizerB = User::create([
            'name' => 'Organizer B',
            'primary_contact_number' => '9800000003',
            'password' => 'secret123',
        ]);

        $schemeA = SavingsScheme::create([
            'title' => 'Organizer A Scheme',
            'organizer_id' => $organizerA->id,
            'total_seats' => 5,
            'total_periods' => 5,
            'installment_amount' => 5000,
            'interest_amount' => 200,
            'khaja_kharcha' => 500,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $schemeB = SavingsScheme::create([
            'title' => 'Organizer B Scheme',
            'organizer_id' => $organizerB->id,
            'total_seats' => 5,
            'total_periods' => 5,
            'installment_amount' => 8000,
            'interest_amount' => 300,
            'khaja_kharcha' => 500,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Organizer A can see Savings Schemes and only lists Scheme A
        $this->actingAs($organizerA);
        $this->assertTrue(SavingsSchemeResource::canViewAny());
        $this->get('/savings-schemes')->assertStatus(200);

        Livewire::test(ListSavingsSchemes::class)
            ->assertCanSeeTableRecords([$schemeA])
            ->assertCanNotSeeTableRecords([$schemeB]);

        $this->get("/savings-schemes/{$schemeA->id}")->assertStatus(200);
        $this->get("/savings-schemes/{$schemeB->id}")->assertStatus(404);

        // Super Admin sees all schemes
        $this->actingAs($admin);
        Livewire::test(ListSavingsSchemes::class)
            ->assertCanSeeTableRecords([$schemeA, $schemeB]);
    }

    public function test_multi_seat_allocation_and_scheme_activates_on_installment_settlement(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'primary_contact_number' => '9800000001',
            'password' => 'secret123',
        ]);
        $admin->assignRole($superAdminRole);

        $member1 = User::create([
            'name' => 'Alice',
            'username' => 'alice99',
            'primary_contact_number' => '9800000011',
            'password' => 'secret123',
        ]);

        $member2 = User::create([
            'name' => 'Bob',
            'username' => 'bob77',
            'primary_contact_number' => '9800000012',
            'password' => 'secret123',
        ]);

        $this->assertSame('Alice (alice99)', ManageSavingsScheme::formatUserSelectLabel($member1));

        $scheme = SavingsScheme::create([
            'title' => 'Draft Multi Seat Scheme',
            'organizer_id' => $admin->id,
            'total_seats' => 1,
            'total_periods' => 1,
            'installment_amount' => 5000,
            'interest_amount' => 200,
            'khaja_kharcha' => 2000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        app(SavingsSchemeService::class)->initializeScheme($scheme);

        $this->actingAs($admin);

        // Allocate 2 seats each to Alice and Bob (4 new seats -> total 5 seats)
        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme])
            ->callAction('assign_seat', [
                'user_ids' => [$member1->id, $member2->id],
                'seats_per_user' => 2,
            ])
            ->assertHasNoErrors();

        $scheme->refresh();
        $this->assertEquals(5, $scheme->seats()->count());
        $this->assertEquals(2, $scheme->seats()->where('user_id', $member1->id)->count());
        $this->assertEquals(2, $scheme->seats()->where('user_id', $member2->id)->count());
        $this->assertEquals('draft', $scheme->status);

        // Settle one installment -> scheme automatically becomes active
        $period1 = $scheme->periods()->where('period_number', 1)->first();
        app(SavingsSchemeService::class)->generatePeriodInstallments($scheme, $period1);
        $installment = $period1->installments()->where('user_id', $member1->id)->first();

        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme])
            ->call('markInstallmentPaid', $installment->id)
            ->assertNotified('Installment Marked as Paid');

        $this->assertEquals('paid', $installment->fresh()->status);
        $this->assertEquals('active', $scheme->fresh()->status);
    }

    public function test_partial_and_full_multi_seat_settlement_with_date_and_auto_round_completion(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $organizer = User::create([
            'name' => 'Organizer',
            'username' => 'org1',
            'primary_contact_number' => '9800000001',
            'password' => 'secret123',
        ]);
        $organizer->assignRole($superAdminRole);

        $member = User::create([
            'name' => 'Multi Seat Member',
            'username' => 'multimember',
            'primary_contact_number' => '9800000022',
            'password' => 'secret123',
        ]);

        $scheme = SavingsScheme::create([
            'title' => 'Auto Complete Round Scheme',
            'organizer_id' => $organizer->id,
            'total_seats' => 1,
            'total_periods' => 1,
            'installment_amount' => 5000,
            'interest_amount' => 200,
            'khaja_kharcha' => 2000,
            'frequency' => 'monthly',
            'start_date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $service = app(SavingsSchemeService::class);
        $service->initializeScheme($scheme);
        $service->allocateSeat($scheme, $member);
        $service->allocateSeat($scheme, $member);

        $scheme->refresh();
        $period1 = $scheme->periods()->where('period_number', 1)->first();
        $service->generatePeriodInstallments($scheme, $period1);

        $this->actingAs($organizer);

        // Grouped list shows 1 row for Multi Seat Member with Seat #2, #3
        $component = Livewire::test(ManageSavingsScheme::class, ['record' => $scheme]);
        $component->assertSee('Seat #2, #3');

        // 1. Partial settlement of Rs. 4,000 on 2026-11-10
        $component->call('promptUserInstallmentPayment', $member->id, $period1->id)
            ->set('installmentPaymentAmount', 4000)
            ->set('installmentPaidAt', '2026-11-10')
            ->call('confirmMarkInstallmentPaid')
            ->assertNotified('Installment Marked as Paid');

        $scheme->refresh();
        $this->assertEquals('active', $scheme->status);
        $this->assertEquals(1, $scheme->current_period);

        $memberInsts = $period1->installments()->where('user_id', $member->id)->orderBy('scheme_seat_id')->get();
        $this->assertEquals(4000.00, (float) $memberInsts[0]->paid_amount);
        $this->assertEquals('partial', $memberInsts[0]->status);
        $this->assertEquals('2026-11-10', $memberInsts[0]->paid_at->format('Y-m-d'));
        $this->assertEquals(0.00, (float) $memberInsts[1]->paid_amount);

        // 2. Remaining settlement of Rs. 6,000 -> auto-settles Period #1 & advances to Period #2
        $component->call('promptUserInstallmentPayment', $member->id, $period1->id)
            ->set('installmentPaymentAmount', 6000)
            ->set('installmentPaidAt', '2026-11-12')
            ->call('confirmMarkInstallmentPaid')
            ->assertNotified('Installment Marked as Paid');

        $scheme->refresh();
        $period1->refresh();
        $this->assertEquals('completed', $period1->status);
        $this->assertEquals('paid', $period1->payout_status);
        $this->assertEquals(10000.00, (float) $period1->paid_amount); // 3 * 5k - 5k own seat
        $this->assertEquals(2, $scheme->current_period);
        $this->assertEquals('in_progress', $scheme->periods()->where('period_number', 2)->first()->status);
    }
}
