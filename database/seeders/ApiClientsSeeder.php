<?php

namespace Database\Seeders;

use App\Models\ApiClient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApiClientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create service user for OAuth client_credentials grant
        $serviceUser = User::updateOrCreate(
            ['email' => 'api-service@mycanopy.verbeek.ug'],
            [
                'name' => 'API Service User',
                'email' => 'api-service@mycanopy.verbeek.ug',
                'password' => Hash::make('Kbrvr#0891'),
                'role' => 'admin',
            ]
        );

        // Create a sample API client for external apps
        $client = ApiClient::updateOrCreate(
            ['name' => 'MyCanopy Mobile App'],
            [
                'uuid' => ApiClient::generateUuid(),
                'client_id' => 'mycanopy-mobile-app',
                'client_secret' => Hash::make('7Xk9Lm2pQv4rT8yB1w'),
                'password_client' => true,
                'personal_access_client' => false,
                'revoked' => false,
                'scopes' => ['*'],
            ]
        );

        $this->command->info('API Service User created: api-service@mycanopy.verbeek.ug');
        $this->command->info('Sample API Client created: mycanopy-mobile-app');
        $this->command->warn('Remember to change the passwords in production!');
    }
}
