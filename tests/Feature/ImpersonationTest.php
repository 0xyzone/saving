<?php

namespace Tests\Feature;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use STS\FilamentImpersonate\Facades\Impersonation;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => Utils::getSuperAdminName(), 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => Utils::getPanelUserRoleName(), 'guard_name' => 'web']);
    }

    public function test_super_admin_can_impersonate_and_access_panel_without_403(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'primary_contact_number' => '9801000001',
            'password' => 'secret',
        ]);
        $admin->assignRole(Utils::getSuperAdminName());

        $member = User::create([
            'name' => 'Member User',
            'primary_contact_number' => '9801000002',
            'password' => 'secret',
        ]);

        $this->assertTrue($admin->canImpersonate());
        $this->assertTrue($member->canBeImpersonated());

        // Authenticate as Admin
        $this->actingAs($admin);

        // Enter impersonation
        Impersonation::enter($admin, $member, 'web');
        $this->assertTrue(Impersonation::isImpersonating());

        // Panel check should evaluate to true during impersonation
        $panel = Filament::getCurrentOrDefaultPanel();
        $this->assertTrue($member->canAccessPanel($panel));

        // Requesting the panel dashboard should NOT return 403
        $response = $this->get('/');
        $response->assertStatus(200);

        // Leaving impersonation
        $leaveResponse = $this->get('/filament-impersonate/leave');
        $leaveResponse->assertRedirect();
        $this->assertFalse(Impersonation::isImpersonating());
    }

    public function test_newly_created_user_automatically_receives_panel_user_role(): void
    {
        $user = User::create([
            'name' => 'Fresh Member',
            'primary_contact_number' => '9801000099',
            'password' => 'secret',
        ]);

        $this->assertTrue($user->hasRole(Utils::getPanelUserRoleName()));

        $panel = Filament::getCurrentOrDefaultPanel();
        $this->assertTrue($user->canAccessPanel($panel));
    }
}
