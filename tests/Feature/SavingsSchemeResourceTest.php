<?php

namespace Tests\Feature;

use App\Filament\Resources\SavingsSchemes\Pages\ManageSavingsScheme;
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
}
