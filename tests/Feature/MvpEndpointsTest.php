<?php

namespace Tests\Feature;

use App\Models\Corporation;
use App\Models\Location;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MvpEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_property_listing_supports_filters(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Kampala Homes',
            'domain' => 'kampala-homes.example.test',
        ]);

        $owner = User::factory()->create(['corporation_id' => $tenant->id]);

        $matching = Property::query()->create([
            'corporation_id' => $tenant->id,
            'user_id' => $owner->id,
            'title' => 'Kololo Apartment',
            'description' => 'Two-bedroom apartment',
            'price_ugx' => 1800000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'bedrooms' => 2,
            'bathrooms' => 2,
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Plot 12 Kololo',
            'latitude' => 0.337,
            'longitude' => 32.585,
            'status' => 'published',
            'published_at' => now(),
        ]);

        Property::query()->create([
            'corporation_id' => $tenant->id,
            'user_id' => $owner->id,
            'title' => 'Draft Property',
            'description' => 'Should not be listed publicly',
            'price_ugx' => 1500000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Draft address',
            'status' => 'draft',
        ]);

        Property::query()->create([
            'corporation_id' => $tenant->id,
            'user_id' => $owner->id,
            'title' => 'Entebbe House',
            'description' => 'Different district',
            'price_ugx' => 2500000,
            'listing_type' => 'rent',
            'property_type' => 'house',
            'bedrooms' => 4,
            'bathrooms' => 3,
            'district' => 'Wakiso',
            'city' => 'Entebbe',
            'address' => 'Airport Road',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/public/properties?district=Kampala&property_type=apartment&listing_type=rent&min_price=1000000&max_price=2000000&bedrooms=2');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_guest_cannot_create_property(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Guest Guard',
            'domain' => 'guest-guard.example.test',
        ]);

        $owner = User::factory()->create(['corporation_id' => $tenant->id]);

        $payload = [
            'title' => 'Test Listing',
            'description' => 'Test description',
            'price_ugx' => 1200000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'bedrooms' => 2,
            'bathrooms' => 1,
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Test Street',
            'status' => 'draft',
            'user_id' => $owner->id,
        ];

        $this->postJson('/api/tenant/properties', $payload)->assertUnauthorized();
    }

    public function test_authenticated_user_without_tenant_context_cannot_create_property(): void
    {
        $user = User::factory()->create(['corporation_id' => null]);
        Sanctum::actingAs($user);

        $payload = [
            'title' => 'No Tenant Listing',
            'description' => 'Missing tenant should fail',
            'price_ugx' => 1000000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'No tenant street',
            'status' => 'draft',
        ];

        $this->postJson('/api/tenant/properties', $payload)->assertForbidden();
    }

    public function test_property_update_is_forbidden_for_non_owner_within_tenant(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Authz Tenant',
            'domain' => 'authz.example.test',
        ]);

        $owner = User::factory()->create(['corporation_id' => $tenant->id]);
        $otherUser = User::factory()->create(['corporation_id' => $tenant->id]);

        $property = Property::query()->create([
            'corporation_id' => $tenant->id,
            'user_id' => $owner->id,
            'title' => 'Owner Listing',
            'description' => 'Owner only update',
            'price_ugx' => 2000000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Owner street',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($otherUser);

        $this->putJson("/api/tenant/properties/{$property->id}", [
            'title' => 'Should Not Change',
        ])->assertForbidden();
    }

    public function test_locations_endpoint_returns_seeded_uganda_locations(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Location Tenant',
            'domain' => 'location.example.test',
        ]);

        $user = User::factory()->create(['corporation_id' => $tenant->id]);

        Location::query()->create([
            'country' => 'Uganda',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'slug' => 'uganda-kampala-kampala-central',
            'is_active' => true,
        ]);

        Location::query()->create([
            'country' => 'Uganda',
            'district' => 'Wakiso',
            'city' => 'Entebbe',
            'slug' => 'uganda-wakiso-entebbe',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tenant/locations');

        $response->assertOk();
        $response->assertJsonFragment(['district' => 'Kampala']);
        $response->assertJsonFragment(['district' => 'Wakiso']);
    }

    public function test_message_store_endpoint_sends_message_for_property(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Messaging Tenant',
            'domain' => 'messaging.example.test',
        ]);

        $owner = User::factory()->create(['corporation_id' => $tenant->id]);
        $sender = User::factory()->create(['corporation_id' => $tenant->id]);

        $property = Property::query()->create([
            'corporation_id' => $tenant->id,
            'user_id' => $owner->id,
            'title' => 'Message Listing',
            'description' => 'Listing for message flow',
            'price_ugx' => 1700000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Message street',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Sanctum::actingAs($sender);

        $payload = [
            'property_id' => $property->id,
            'body' => 'Is this property still available for immediate move-in?',
        ];

        $response = $this->postJson('/api/tenant/messages', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('messages', [
            'property_id' => $property->id,
            'sender_id' => $sender->id,
            'receiver_id' => $owner->id,
            'body' => $payload['body'],
        ]);

        $message = Message::query()->latest('id')->first();

        $this->assertNotNull($message);
        $this->assertSame($property->id, $message->property_id);
    }
}
