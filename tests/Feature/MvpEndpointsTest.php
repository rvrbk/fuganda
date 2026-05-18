<?php

namespace Tests\Feature;

use App\Mail\NewPropertyMessageMail;
use App\Models\Corporation;
use App\Models\Location;
use App\Models\Message;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_authenticated_owned_properties_filter_returns_only_current_users_listings(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Owned Filter Tenant',
            'domain' => 'owned-filter.example.test',
        ]);

        $seller = User::factory()->seller()->create(['corporation_id' => $tenant->id]);
        $otherSeller = User::factory()->seller()->create(['corporation_id' => $tenant->id]);

        $ownedProperty = Property::query()->create([
            'corporation_id' => $tenant->id,
            'user_id' => $seller->id,
            'title' => 'Seller Owned Listing',
            'description' => 'Should be visible in owned scope',
            'price_ugx' => 1200000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Owned scope street',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Property::query()->create([
            'corporation_id' => $tenant->id,
            'user_id' => $otherSeller->id,
            'title' => 'Other Seller Listing',
            'description' => 'Should be hidden in owned scope',
            'price_ugx' => 1300000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Other scope street',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Sanctum::actingAs($seller);

        $response = $this->getJson('/api/properties?owned=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $ownedProperty->id);
        $response->assertJsonPath('data.0.user_id', $seller->id);
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

        $this->postJson('/api/properties', $payload)->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_property_on_auth_route(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Create Guard',
            'domain' => 'create-guard.example.test',
        ]);

        $user = User::factory()->seller()->create(['corporation_id' => $tenant->id]);
        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Auth Route Listing',
            'description' => 'Authenticated seller can create listing',
            'price_ugx' => 1000000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Auth route street',
            'status' => 'draft',
        ];

        $response = $this->postJson('/api/properties', $payload);

        $response->assertCreated();
        $response->assertJsonPath('user_id', $user->id);

        $this->assertDatabaseHas('properties', [
            'title' => 'Auth Route Listing',
            'user_id' => $user->id,
            'corporation_id' => $tenant->id,
        ]);
    }

    public function test_property_creation_accepts_ugx_and_usd(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Currency Tenant',
            'domain' => 'currency.example.test',
        ]);

        $seller = User::factory()->seller()->create(['corporation_id' => $tenant->id]);
        Sanctum::actingAs($seller);

        $basePayload = [
            'title' => 'Currency Listing',
            'description' => 'Currency validation coverage listing',
            'price_ugx' => 1000000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Currency street',
            'status' => 'draft',
        ];

        $ugxResponse = $this->postJson('/api/properties', [
            ...$basePayload,
            'title' => 'Currency UGX Listing',
            'price_currency' => 'UGX',
        ]);

        $ugxResponse->assertCreated();
        $ugxResponse->assertJsonPath('price_currency', 'UGX');

        $usdResponse = $this->postJson('/api/properties', [
            ...$basePayload,
            'title' => 'Currency USD Listing',
            'price_currency' => 'USD',
        ]);

        $usdResponse->assertCreated();
        $usdResponse->assertJsonPath('price_currency', 'USD');

        $this->assertDatabaseHas('properties', [
            'title' => 'Currency UGX Listing',
            'price_currency' => 'UGX',
        ]);

        $this->assertDatabaseHas('properties', [
            'title' => 'Currency USD Listing',
            'price_currency' => 'USD',
        ]);
    }

    public function test_property_creation_rejects_invalid_currency(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Currency Reject Tenant',
            'domain' => 'currency-reject.example.test',
        ]);

        $seller = User::factory()->seller()->create(['corporation_id' => $tenant->id]);
        Sanctum::actingAs($seller);

        $payload = [
            'title' => 'Invalid Currency Listing',
            'description' => 'Invalid currency should be rejected when validated',
            'price_ugx' => 1000000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Invalid currency street',
            'status' => 'draft',
            'price_currency' => 'EUR',
        ];

        $response = $this->postJson('/api/properties', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['price_currency']);
    }

    public function test_buyer_cannot_create_property_on_auth_route(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Buyer Guard',
            'domain' => 'buyer-guard.example.test',
        ]);

        $buyer = User::factory()->buyer()->create(['corporation_id' => $tenant->id]);
        Sanctum::actingAs($buyer);

        $payload = [
            'title' => 'Buyer Route Listing',
            'description' => 'Buyer should not be allowed to create listing',
            'price_ugx' => 1000000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Buyer route street',
            'status' => 'draft',
        ];

        $response = $this->postJson('/api/properties', $payload);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'This action is unauthorized.');

        $this->assertDatabaseMissing('properties', [
            'title' => 'Buyer Route Listing',
            'user_id' => $buyer->id,
            'corporation_id' => $tenant->id,
        ]);
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

        $this->putJson("/api/properties/{$property->id}", [
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

        $response = $this->getJson('/api/locations');

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

        Mail::fake();

        Sanctum::actingAs($sender);

        $payload = [
            'property_id' => $property->id,
            'body' => 'Is this property still available for immediate move-in?',
        ];

        $response = $this->postJson('/api/messages', $payload);

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

        Mail::assertSent(NewPropertyMessageMail::class, function (NewPropertyMessageMail $mail) use ($owner): bool {
            return $mail->hasTo($owner->email);
        });

        Sanctum::actingAs($owner);

        $this->getJson('/api/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('unread_messages', 1);

        $this->getJson('/api/messages')->assertOk();

        $this->getJson('/api/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('unread_messages', 0);
    }

    public function test_message_store_endpoint_can_reply_to_counterpart_with_receiver_id(): void
    {
        $tenant = Corporation::query()->create([
            'name' => 'Messaging Reply Tenant',
            'domain' => 'messaging-reply.example.test',
        ]);

        $owner = User::factory()->create(['corporation_id' => $tenant->id]);
        $buyer = User::factory()->create(['corporation_id' => $tenant->id]);

        $property = Property::query()->create([
            'corporation_id' => $tenant->id,
            'user_id' => $owner->id,
            'title' => 'Reply Listing',
            'description' => 'Listing for reply message flow',
            'price_ugx' => 1500000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Reply street',
            'status' => 'published',
            'published_at' => now(),
        ]);

        Message::query()->create([
            'property_id' => $property->id,
            'sender_id' => $buyer->id,
            'receiver_id' => $owner->id,
            'body' => 'Hi, is this available?',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/messages', [
            'property_id' => $property->id,
            'receiver_id' => $buyer->id,
            'body' => 'Yes, still available. When would you like to view it?',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('messages', [
            'property_id' => $property->id,
            'sender_id' => $owner->id,
            'receiver_id' => $buyer->id,
            'body' => 'Yes, still available. When would you like to view it?',
        ]);
    }
}
