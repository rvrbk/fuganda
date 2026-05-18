<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessagesAccessWithoutTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_without_tenant_context_can_access_messages_index(): void
    {
        $user = User::factory()->buyer()->create([
            'corporation_id' => null,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/messages')
            ->assertOk()
            ->assertJsonMissing([
                'message' => 'No tenant context found for the authenticated user.',
            ]);
    }
}
