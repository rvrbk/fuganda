<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\SellerPublishFee;
use App\Models\SellerSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PropertyCreateWithoutTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_without_corporation_can_create_property(): void
    {
        $user = User::factory()->seller()->create([
            'corporation_id' => null,
        ]);

        SellerSubscription::query()->create([
            'user_id' => $user->id,
            'plan_code' => 'starter_monthly',
            'amount_ugx' => 39000,
            'currency' => 'UGX',
            'status' => 'active',
            'started_at' => now(),
            'renews_at' => now()->addMonth(),
        ]);

        Sanctum::actingAs($user);

        $payload = [
            'title' => 'Standalone Seller Listing',
            'description' => 'A valid listing from a non-tenant seller.',
            'price_ugx' => 950000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'bedrooms' => 2,
            'bathrooms' => 1,
            'district' => 'Wakiso',
            'city' => 'Nansana',
            'address' => 'Nansana, Wakiso',
            'latitude' => 0.3635,
            'longitude' => 32.5275,
            'status' => 'published',
        ];

        $response = $this->postJson('/api/properties', $payload);

        $response->assertCreated();

        $propertyId = $response->json('id') ?? $response->json('data.id');
        $this->assertNotNull($propertyId);

        $property = Property::query()->find($propertyId);
        $this->assertNotNull($property);
        $this->assertSame($user->id, (int) $property->user_id);
        $this->assertNull($property->corporation_id);

        $this->assertDatabaseHas('seller_publish_fees', [
            'user_id' => $user->id,
            'property_id' => $property->id,
            'status' => 'charged',
        ]);

        $fee = SellerPublishFee::query()->where('property_id', $property->id)->first();
        $this->assertNotNull($fee);
        $this->assertSame(500, (int) $fee->amount_ugx);
    }
}
