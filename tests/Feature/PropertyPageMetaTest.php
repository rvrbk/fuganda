<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyPageMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    private function makeProperty(array $overrides = []): Property
    {
        $seller = User::factory()->seller()->create();

        return Property::query()->create(array_merge([
            'user_id' => $seller->id,
            'corporation_id' => $seller->corporation_id,
            'title' => 'Sunny house in Entebbe',
            'description' => 'Three bedrooms near the lake.',
            'price_ugx' => 1000000,
            'listing_type' => 'rent',
            'property_type' => 'house',
            'district' => 'Wakiso',
            'city' => 'Entebbe',
            'address' => 'Lake Road',
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }

    public function test_published_property_page_renders_its_own_meta_tags(): void
    {
        $property = $this->makeProperty();
        PropertyImage::query()->create(['property_id' => $property->id, 'path' => 'property-images/front.jpg', 'sort_order' => 0]);

        $response = $this->get('/properties/'.$property->id);

        $response->assertOk();
        $response->assertSee('<title>Sunny house in Entebbe | MyCanopy</title>', false);
        $response->assertSee('content="Three bedrooms near the lake."', false);
        $response->assertSee('property="og:image" content="'.url('/storage/property-images/front.jpg').'"', false);
        $response->assertSee('RealEstateListing', false);
    }

    public function test_property_without_image_falls_back_to_the_site_image(): void
    {
        $property = $this->makeProperty();

        $this->get('/properties/'.$property->id)
            ->assertOk()
            ->assertSee('property="og:image" content="'.url('/og.png').'"', false);
    }

    public function test_unpublished_property_page_gets_the_default_meta_tags(): void
    {
        $property = $this->makeProperty(['status' => 'draft', 'title' => 'Secret draft']);

        $this->get('/properties/'.$property->id)
            ->assertOk()
            ->assertDontSee('Secret draft')
            ->assertSee('MyCanopy — Uganda Property Listings', false);
    }
}
