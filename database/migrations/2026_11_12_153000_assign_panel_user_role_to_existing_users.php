<?php

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $panelRoleName = Utils::getPanelUserRoleName();
        $guard = Utils::getFilamentAuthGuard();

        $panelUserRole = Role::firstOrCreate([
            'name' => $panelRoleName,
            'guard_name' => $guard,
        ]);

        // Assign panel_user role to any existing users who have no roles assigned
        $usersWithoutRoles = User::whereDoesntHave('roles')->get();
        foreach ($usersWithoutRoles as $user) {
            $user->assignRole($panelUserRole);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reverse needed
    }
};
