<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_authenticated_user_can_access_dashboard_payload(): void
    {
        $user = User::factory()->create(['corporation_id' => null]);

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('stats.property_count', 0)
            ->assertJsonPath('stats.unread_messages', 0)
            ->assertJsonMissingPath('tenant');
    }
}
