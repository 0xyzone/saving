<?php

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $panelUserRole = Role::where('name', Utils::getPanelUserRoleName())->first();

        if ($panelUserRole) {
            foreach (['ViewAny:SavingsScheme', 'View:SavingsScheme'] as $permissionName) {
                if (Permission::where('name', $permissionName)->where('guard_name', $panelUserRole->guard_name)->exists()) {
                    $panelUserRole->revokePermissionTo($permissionName);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $panelUserRole = Role::where('name', Utils::getPanelUserRoleName())->first();

        if ($panelUserRole) {
            foreach (['ViewAny:SavingsScheme', 'View:SavingsScheme'] as $permissionName) {
                if (Permission::where('name', $permissionName)->where('guard_name', $panelUserRole->guard_name)->exists()) {
                    $panelUserRole->givePermissionTo($permissionName);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
