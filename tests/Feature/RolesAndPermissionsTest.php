<?php

namespace Tests\Feature;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\SavingsSchemes\Pages\ManageSavingsScheme;
use App\Filament\Resources\SavingsSchemes\SavingsSchemeResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\SavingsScheme;
use App\Models\User;
use App\Policies\SavingsSchemePolicy;
use App\Services\SavingsSchemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_roles_resource(): void
    {
        $role = Role::create(['name' => 'super_admin']);
        $admin = User::create([
            'name' => 'Super Admin',
            'primary_contact_number' => '9802350986',
            'password' => 'secret123',
        ]);
        $admin->assignRole($role);

        $this->actingAs($admin);

        $response = $this->get('/shield/roles');
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_roles_resource(): void
    {
        $user = User::create([
            'name' => 'Standard User',
            'primary_contact_number' => '9811111111',
            'password' => 'secret123',
        ]);

        $this->actingAs($user);

        $response = $this->get('/shield/roles');
        $response->assertStatus(403);
    }

    public function test_enhanced_resource_permissions_are_declared_on_user_resource(): void
    {
        $permissions = UserResource::getShieldResourcePermissions();

        $this->assertArrayHasKey('ManageRoles', $permissions);
        $this->assertArrayHasKey('ManagePaymentOptions', $permissions);
        $this->assertArrayHasKey('Export', $permissions);
    }

    public function test_can_shield_evaluates_correctly_for_super_admin_and_standard_user(): void
    {
        Permission::findOrCreate('ManageRoles:User', 'web');

        $superAdminRole = Role::create(['name' => 'super_admin']);
        $managerRole = Role::create(['name' => 'manager']);
        $managerRole->givePermissionTo('ManageRoles:User');

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'primary_contact_number' => '9800000001',
            'password' => 'secret',
        ]);
        $superAdmin->assignRole($superAdminRole);

        $manager = User::create([
            'name' => 'Manager User',
            'primary_contact_number' => '9800000002',
            'password' => 'secret',
        ]);
        $manager->assignRole($managerRole);

        $regular = User::create([
            'name' => 'Regular User',
            'primary_contact_number' => '9800000003',
            'password' => 'secret',
        ]);

        // Super Admin bypass
        $this->actingAs($superAdmin);
        $this->assertTrue(UserResource::canShield('ManageRoles'));
        $this->assertTrue(UserResource::canShield('ManagePaymentOptions'));

        // Manager with specific permission
        $this->actingAs($manager);
        $this->assertTrue(UserResource::canShield('ManageRoles'));
        $this->assertFalse(UserResource::canShield('ManagePaymentOptions'));

        // Regular user with no permissions
        $this->actingAs($regular);
        $this->assertFalse(UserResource::canShield('ManageRoles'));
        $this->assertFalse(UserResource::canShield('ManagePaymentOptions'));
    }

    public function test_roles_resource_renders_enhanced_permission_form_tabs(): void
    {
        $role = Role::create(['name' => 'super_admin']);
        $admin = User::create([
            'name' => 'Super Admin',
            'primary_contact_number' => '9802350986',
            'password' => 'secret123',
        ]);
        $admin->assignRole($role);

        $this->actingAs($admin);

        Livewire::test(ListRoles::class)
            ->assertSuccessful();

        Livewire::test(CreateRole::class)
            ->assertSuccessful()
            ->assertSee('Access Management')
            ->assertSee('Savings Management')
            ->assertSee('Manage user roles & permissions');
    }

    public function test_savings_scheme_enhanced_permissions_are_declared(): void
    {
        $permissions = SavingsSchemeResource::getShieldResourcePermissions();

        $this->assertArrayHasKey('DeclareWinner', $permissions);
        $this->assertArrayHasKey('ManageInstallments', $permissions);
        $this->assertArrayHasKey('ManageSeats', $permissions);
    }

    public function test_savings_scheme_can_shield_evaluates_correctly(): void
    {
        Permission::findOrCreate('DeclareWinner:SavingsScheme', 'web');
        Permission::findOrCreate('ManageInstallments:SavingsScheme', 'web');
        Permission::findOrCreate('ManageSeats:SavingsScheme', 'web');

        $superAdminRole = Role::create(['name' => 'super_admin']);
        $officerRole = Role::create(['name' => 'scheme_officer']);
        $officerRole->givePermissionTo('ManageInstallments:SavingsScheme');

        $superAdmin = User::create([
            'name' => 'Super Admin',
            'primary_contact_number' => '9800000001',
            'password' => 'secret',
        ]);
        $superAdmin->assignRole($superAdminRole);

        $officer = User::create([
            'name' => 'Scheme Officer',
            'primary_contact_number' => '9800000002',
            'password' => 'secret',
        ]);
        $officer->assignRole($officerRole);

        $regular = User::create([
            'name' => 'Regular User',
            'primary_contact_number' => '9800000003',
            'password' => 'secret',
        ]);

        // Super Admin bypass
        $this->actingAs($superAdmin);
        $this->assertTrue(SavingsSchemeResource::canShield('DeclareWinner'));
        $this->assertTrue(SavingsSchemeResource::canShield('ManageInstallments'));
        $this->assertTrue(SavingsSchemeResource::canShield('ManageSeats'));

        // Officer with only ManageInstallments
        $this->actingAs($officer);
        $this->assertFalse(SavingsSchemeResource::canShield('DeclareWinner'));
        $this->assertTrue(SavingsSchemeResource::canShield('ManageInstallments'));
        $this->assertFalse(SavingsSchemeResource::canShield('ManageSeats'));

        // Regular user
        $this->actingAs($regular);
        $this->assertFalse(SavingsSchemeResource::canShield('DeclareWinner'));
        $this->assertFalse(SavingsSchemeResource::canShield('ManageInstallments'));
        $this->assertFalse(SavingsSchemeResource::canShield('ManageSeats'));
    }

    public function test_savings_scheme_policy_fine_grained_checks(): void
    {
        Permission::findOrCreate('DeclareWinner:SavingsScheme', 'web');
        Permission::findOrCreate('ManageInstallments:SavingsScheme', 'web');
        Permission::findOrCreate('ManageSeats:SavingsScheme', 'web');

        $managerRole = Role::create(['name' => 'manager']);
        $managerRole->givePermissionTo(['DeclareWinner:SavingsScheme', 'ManageSeats:SavingsScheme']);

        $manager = User::create([
            'name' => 'Manager',
            'primary_contact_number' => '9800000010',
            'password' => 'secret',
        ]);
        $manager->assignRole($managerRole);

        $scheme = SavingsScheme::create([
            'organizer_id' => $manager->id,
            'title' => 'Festive Dhukuti',
            'total_seats' => 5,
            'installment_amount' => 10000,
            'interest_amount' => 500,
            'khaja_kharcha' => 1000,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $policy = new SavingsSchemePolicy;

        $regular = User::create([
            'name' => 'Member',
            'primary_contact_number' => '9800000011',
            'password' => 'secret',
        ]);

        $this->assertTrue($policy->declareWinner($manager, $scheme));
        $this->assertTrue($policy->manageSeats($manager, $scheme));
        $this->assertFalse($policy->manageInstallments($manager, $scheme));

        $this->assertFalse($policy->declareWinner($regular, $scheme));
        $this->assertFalse($policy->manageSeats($regular, $scheme));
        $this->assertFalse($policy->manageInstallments($regular, $scheme));
    }

    public function test_manage_savings_scheme_page_permission_enforcement(): void
    {
        Permission::findOrCreate('ViewAny:SavingsScheme', 'web');
        Permission::findOrCreate('View:SavingsScheme', 'web');
        Permission::findOrCreate('DeclareWinner:SavingsScheme', 'web');
        Permission::findOrCreate('ManageInstallments:SavingsScheme', 'web');

        $admin = User::create([
            'name' => 'Scheme Admin',
            'primary_contact_number' => '9802350999',
            'password' => 'secret',
        ]);
        $adminRole = Role::create(['name' => 'super_admin']);
        $admin->assignRole($adminRole);

        $scheme = SavingsScheme::create([
            'organizer_id' => $admin->id,
            'title' => 'Test Scheme',
            'total_seats' => 3,
            'installment_amount' => 5000,
            'interest_amount' => 200,
            'khaja_kharcha' => 500,
            'start_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        app(SavingsSchemeService::class)->initializeScheme($scheme);
        $period = $scheme->periods()->first();
        app(SavingsSchemeService::class)->generatePeriodInstallments($scheme, $period);

        $installment = $period->installments()->first();

        // Viewer user has view access to the scheme, but lacks ManageInstallments permission
        $viewerRole = Role::create(['name' => 'scheme_viewer']);
        $viewerRole->givePermissionTo(['ViewAny:SavingsScheme', 'View:SavingsScheme']);

        $viewerUser = User::create([
            'name' => 'Viewer User',
            'primary_contact_number' => '9800000020',
            'password' => 'secret',
        ]);
        $viewerUser->assignRole($viewerRole);
        $this->actingAs($viewerUser);

        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme])
            ->call('markInstallmentPaid', $installment->id)
            ->assertNotified('Unauthorized');

        $this->assertEquals('pending', $installment->fresh()->status);

        // Admin can mark installment paid
        $this->actingAs($admin);

        Livewire::test(ManageSavingsScheme::class, ['record' => $scheme])
            ->call('markInstallmentPaid', $installment->id)
            ->assertNotified('Installment Marked as Paid');

        $this->assertEquals('paid', $installment->fresh()->status);
    }
}
