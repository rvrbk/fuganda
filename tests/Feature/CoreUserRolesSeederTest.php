<?php

namespace Tests\Feature;

use Database\Seeders\CoreUserRolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreUserRolesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_user_roles_seeder_creates_all_expected_role_users(): void
    {
        $this->seed(CoreUserRolesSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'buyer@example.com',
            'role' => 'buyer',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'seller@example.com',
            'role' => 'seller',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'agent@example.com',
            'role' => 'agent',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => 'buyer',
        ]);
    }
}