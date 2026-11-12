<?php

namespace Database\Seeders;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Generate shield core permissions
        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'panel',
            '--option' => 'permissions',
            '--no-interaction' => true,
        ]);

        // Generate enhanced resource permissions
        Artisan::call('shield:generate-enhanced-resources', [
            '--all-panels' => true,
        ]);

        // Ensure roles exist
        $superAdminRole = Role::firstOrCreate([
            'name' => Utils::getSuperAdminName(),
            'guard_name' => Utils::getFilamentAuthGuard(),
        ]);

        $panelUserRole = Role::firstOrCreate([
            'name' => Utils::getPanelUserRoleName(),
            'guard_name' => Utils::getFilamentAuthGuard(),
        ]);

        // Give all permissions to super_admin
        $superAdminRole->syncPermissions(Permission::all());

        // Basic permissions for panel user
        $panelUserPermissions = [
            'View:EditProfilePage',
            'ViewAny:User',
            'View:User',
        ];
        foreach ($panelUserPermissions as $perm) {
            if (Permission::where('name', $perm)->exists()) {
                $panelUserRole->givePermissionTo($perm);
            }
        }

        // Assign super_admin to initial administrator if exists
        $firstUser = User::first();
        if ($firstUser && ! $firstUser->hasRole(Utils::getSuperAdminName())) {
            $firstUser->assignRole($superAdminRole);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
