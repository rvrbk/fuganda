<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaSessionLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_spa_session_login_can_access_auth_me_endpoint(): void
    {
        $password = 'secret-pass-123';
        $user = User::factory()->create([
            'password' => $password,
        ]);

        $headers = [
            'Accept' => 'application/json',
            'Origin' => 'http://fuganda.test',
            'Referer' => 'http://fuganda.test',
        ];

        $this->withServerVariables(['HTTP_HOST' => 'fuganda.test'])
            ->withHeaders($headers)
            ->get('/sanctum/csrf-cookie')
            ->assertNoContent();

        $this->withServerVariables(['HTTP_HOST' => 'fuganda.test'])
            ->withHeaders($headers)
            ->post('/login', [
                'email' => $user->email,
                'password' => $password,
            ])
            ->assertSuccessful();

        $this->withServerVariables(['HTTP_HOST' => 'fuganda.test'])
            ->withHeaders($headers)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }
}
