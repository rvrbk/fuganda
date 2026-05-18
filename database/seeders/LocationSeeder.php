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
                'district' => 'Kampala',
                'city' => 'Kampala Central',
                'slug' => 'uganda-kampala-kampala-central',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Kampala',
                'city' => 'Kawempe',
                'slug' => 'uganda-kampala-kawempe',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Kampala',
                'city' => 'Makindye',
                'slug' => 'uganda-kampala-makindye',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Kampala',
                'city' => 'Nakawa',
                'slug' => 'uganda-kampala-nakawa',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Kampala',
                'city' => 'Rubaga',
                'slug' => 'uganda-kampala-rubaga',
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
                'district' => 'Wakiso',
                'city' => 'Nansana',
                'slug' => 'uganda-wakiso-nansana',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Wakiso',
                'city' => 'Kira',
                'slug' => 'uganda-wakiso-kira',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Wakiso',
                'city' => 'Ssabagabo',
                'slug' => 'uganda-wakiso-ssabagabo',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Jinja',
                'city' => 'Jinja',
                'slug' => 'uganda-jinja-jinja',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Jinja',
                'city' => 'Buwenge',
                'slug' => 'uganda-jinja-buwenge',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Mbarara',
                'city' => 'Mbarara',
                'slug' => 'uganda-mbarara-mbarara',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Gulu',
                'city' => 'Gulu',
                'slug' => 'uganda-gulu-gulu',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Mbale',
                'city' => 'Mbale',
                'slug' => 'uganda-mbale-mbale',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Mukono',
                'city' => 'Mukono',
                'slug' => 'uganda-mukono-mukono',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Mukono',
                'city' => 'Seeta',
                'slug' => 'uganda-mukono-seeta',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Mukono',
                'city' => 'Njeru',
                'slug' => 'uganda-mukono-njeru',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Arua',
                'city' => 'Arua',
                'slug' => 'uganda-arua-arua',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Hoima',
                'city' => 'Hoima',
                'slug' => 'uganda-hoima-hoima',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Lira',
                'city' => 'Lira',
                'slug' => 'uganda-lira-lira',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Masaka',
                'city' => 'Masaka',
                'slug' => 'uganda-masaka-masaka',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Kabale',
                'city' => 'Kabale',
                'slug' => 'uganda-kabale-kabale',
                'is_active' => true,
            ],
            [
                'country' => 'Uganda',
                'district' => 'Soroti',
                'city' => 'Soroti',
                'slug' => 'uganda-soroti-soroti',
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
