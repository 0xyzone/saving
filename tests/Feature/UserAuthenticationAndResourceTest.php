<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAuthenticationAndResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders_with_primary_contact_number(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSeeText('Primary Contact Number');
    }

    public function test_two_step_login_verification_flow(): void
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'primary_contact_number' => '9801122334',
            'password' => 'secret1234',
        ]);

        // 1. Initial state: Password field is not visible, only contact number & 'Continue' action
        $component = Livewire::test(Login::class);
        $component->assertSee('Primary Contact Number');
        $component->assertDontSee('Current password');

        // 2. Submit non-existent number -> fails with validation message & error notification
        $component->set('data.primary_contact_number', '9899999999')
            ->call('verifyContactNumber')
            ->assertHasErrors(['data.primary_contact_number'])
            ->assertNotified('Account not found');

        // 3. Submit valid existing number -> succeeds and exposes password field
        $component->set('data.primary_contact_number', '9801122334')
            ->call('verifyContactNumber')
            ->assertHasNoErrors()
            ->assertSet('isNumberVerified', true);

        // 4. Submit invalid password -> triggers password error notification & validation message
        $component->set('data.password', 'wrong-pass')
            ->call('authenticate')
            ->assertHasErrors(['data.password'])
            ->assertNotified('Authentication failed');

        // 5. Submit correct password -> successfully logs in
        $component->set('data.password', 'secret1234')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_access_users_resource(): void
    {
        $role = Role::create(['name' => 'super_admin']);

        $user = User::create([
            'name' => 'Admin User',
            'primary_contact_number' => '9800000001',
            'password' => 'secret123',
        ]);
        $user->assignRole($role);

        $this->actingAs($user);

        $response = $this->get('/users');
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_access_users_resource(): void
    {
        $user = User::create([
            'name' => 'Regular User',
            'primary_contact_number' => '9800000002',
            'password' => 'secret123',
        ]);

        $this->actingAs($user);

        $response = $this->get('/users');
        $response->assertStatus(403);
    }
}
