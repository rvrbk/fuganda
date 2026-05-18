<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoreUserRolesSeeder extends Seeder
{
    /**
     * Seed one deterministic user per core role.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'role' => 'buyer',
            ],
            [
                'name' => 'Seed Buyer',
                'email' => 'buyer@example.com',
                'role' => 'buyer',
            ],
            [
                'name' => 'Seed Seller',
                'email' => 'seller@example.com',
                'role' => 'seller',
            ],
            [
                'name' => 'Seed Agent',
                'email' => 'agent@example.com',
                'role' => 'agent',
            ],
            [
                'name' => 'Seed Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}