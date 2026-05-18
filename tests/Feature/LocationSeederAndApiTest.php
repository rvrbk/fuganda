<?php

namespace Tests\Feature;

use App\Models\Location;
use Database\Seeders\LocationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationSeederAndApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_seeder_inserts_expected_uganda_records(): void
    {
        $this->seed(LocationSeeder::class);

        $this->assertDatabaseHas('locations', [
            'country' => 'Uganda',
            'district' => 'Kampala',
            'city' => 'Kampala',
            'slug' => 'uganda-kampala-kampala',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('locations', [
            'country' => 'Uganda',
            'district' => 'Wakiso',
            'city' => 'Entebbe',
            'slug' => 'uganda-wakiso-entebbe',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('locations', [
            'country' => 'Uganda',
            'district' => 'Jinja',
            'city' => 'Jinja',
            'slug' => 'uganda-jinja-jinja',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('locations', [
            'country' => 'Uganda',
            'district' => 'Kampala',
            'city' => 'Kawempe',
            'slug' => 'uganda-kampala-kawempe',
            'is_active' => true,
        ]);
    }

    public function test_locations_api_returns_only_active_locations_with_expected_structure(): void
    {
        $this->seed(LocationSeeder::class);

        Location::query()->create([
            'country' => 'Uganda',
            'district' => 'Kampala',
            'city' => 'Namuwongo',
            'slug' => 'uganda-kampala-namuwongo',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/locations');

        $response->assertOk();
        $response->assertJsonStructure([
            '*' => ['id', 'country', 'district', 'city', 'slug', 'is_active', 'created_at', 'updated_at'],
        ]);
        $response->assertJsonMissing([
            'slug' => 'uganda-kampala-namuwongo',
        ]);
        $response->assertJsonFragment([
            'slug' => 'uganda-kampala-kampala',
        ]);

        $kampalaLocations = collect($response->json())
            ->where('district', 'Kampala');

        $this->assertGreaterThan(1, $kampalaLocations->count());

        $this->assertTrue(collect($response->json())->every(fn (array $location): bool => $location['is_active'] === true));
    }
}
