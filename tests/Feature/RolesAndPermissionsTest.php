<?php

namespace Tests\Feature;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
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
            ->assertSee('Manage user roles & permissions');
    }
}
