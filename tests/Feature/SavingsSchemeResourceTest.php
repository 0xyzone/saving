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
}
