<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Seed the application's location reference data.
     */
    public function run(): void
    {
        $locations = [
            [
                'country' => 'Uganda',
                'district' => 'Kampala',
                'city' => 'Kampala',
                'slug' => 'uganda-kampala-kampala',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Wakiso',
                'city' => 'Entebbe',
                'slug' => 'uganda-wakiso-entebbe',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Jinja',
                'city' => 'Jinja',
                'slug' => 'uganda-jinja-jinja',
                'is_active' => true,
            ],
        ];

        foreach ($locations as $location) {
            Location::query()->updateOrCreate(
                ['slug' => $location['slug']],
                $location
            );
        }
    }
}
