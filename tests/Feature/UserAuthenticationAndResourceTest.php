<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_user_can_authenticate_using_primary_contact_number(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'primary_contact_number' => '9812345678',
            'password' => 'secret123',
        ]);

        $this->actingAs($user);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_users_resource(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'primary_contact_number' => '9800000001',
            'password' => 'secret123',
        ]);

        $this->actingAs($user);

        $response = $this->get('/users');
        $response->assertStatus(200);
    }
}
