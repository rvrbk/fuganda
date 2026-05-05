<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TenantRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_route_is_forbidden_without_tenant_context(): void
    {
        $user = User::factory()->create(['corporation_id' => null]);
        Sanctum::actingAs($user);

        $this->getJson('/api/tenant/dashboard')->assertForbidden();
    }

    public function test_tenant_route_is_available_with_tenant_context(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Acme Corp',
            'domain' => 'acme.example.test',
        ]);

        $user = User::factory()->create(['corporation_id' => $tenant->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/tenant/dashboard')
            ->assertOk()
            ->assertJsonPath('tenant.id', $tenant->id)
            ->assertJsonPath('user.id', $user->id);
    }
}
