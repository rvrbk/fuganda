<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_can_create_seller_account_when_role_is_seller(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Seller Signup',
            'email' => 'seller-signup@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'seller',
        ]);

        $response->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'seller-signup@example.com',
            'role' => 'seller',
        ]);
    }

    public function test_authenticated_user_can_access_auth_me_route(): void
    {
        $user = User::factory()->agent()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('email', $user->email)
            ->assertJsonPath('role', $user->role);
    }

    public function test_guest_cannot_access_auth_me_route(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_user_factory_defaults_role_to_buyer(): void
    {
        $user = User::factory()->create();

        $this->assertSame('buyer', $user->role);
    }

    public function test_user_factory_admin_state_sets_admin_role(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertSame('admin', $user->role);
    }
}
