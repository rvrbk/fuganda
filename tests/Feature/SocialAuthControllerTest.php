<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class SocialAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_callback_redirects_to_trusted_origin_from_state(): void
    {
        config()->set('sanctum.stateful', ['localhost', 'localhost:5173', '127.0.0.1', '127.0.0.1:8000']);

        $provider = $this->mockSocialiteProvider(
            email: 'buyer@example.test',
            name: 'Buyer User',
            id: 'google-123'
        );

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $state = base64_encode(json_encode([
            'role' => 'buyer',
            'nonce' => 'abc123',
            'origin' => 'http://localhost:5173',
        ], JSON_THROW_ON_ERROR));

        $response = $this->get('/auth/google/callback?state='.urlencode((string) $state));

        $response->assertRedirect('http://localhost:5173/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'buyer@example.test',
            'role' => 'buyer',
            'oauth_provider' => 'google',
            'oauth_provider_id' => 'google-123',
        ]);
    }

    public function test_google_callback_rejects_untrusted_origin_and_falls_back_to_request_host(): void
    {
        config()->set('sanctum.stateful', ['localhost', 'localhost:5173', '127.0.0.1', '127.0.0.1:8000']);

        $provider = $this->mockSocialiteProvider(
            email: 'seller@example.test',
            name: 'Seller User',
            id: 'google-456'
        );

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $state = base64_encode(json_encode([
            'role' => 'seller',
            'nonce' => 'def456',
            'origin' => 'http://evil.example.test',
        ], JSON_THROW_ON_ERROR));

        $response = $this
            ->withServerVariables(['HTTP_HOST' => 'localhost:8000'])
            ->get('/auth/google/callback?state='.urlencode((string) $state));

        $response->assertRedirect('http://localhost:8000/');
        $this->assertAuthenticated();
    }

    public function test_google_callback_falls_back_to_callback_host_when_state_origin_is_different_host(): void
    {
        config()->set('app.url', 'http://fuganda.test');
        config()->set('sanctum.stateful', ['localhost', 'localhost:8000', 'fuganda.test']);

        $provider = $this->mockSocialiteProvider(
            email: 'cross-host@example.test',
            name: 'Cross Host User',
            id: 'google-cross-host'
        );

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $state = base64_encode(json_encode([
            'role' => 'buyer',
            'nonce' => 'crosshost123',
            'origin' => 'http://fuganda.test',
        ], JSON_THROW_ON_ERROR));

        $response = $this
            ->withServerVariables(['HTTP_HOST' => 'localhost:8000'])
            ->get('/auth/google/callback?state='.urlencode((string) $state));

        $response->assertRedirect('http://localhost:8000/');
        $this->assertAuthenticated();
    }

    public function test_google_callback_error_redirect_uses_trusted_origin_login_page(): void
    {
        config()->set('sanctum.stateful', ['localhost', 'localhost:5173', '127.0.0.1', '127.0.0.1:8000']);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andThrow(new RuntimeException('OAuth failed'));

        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $state = base64_encode(json_encode([
            'role' => 'buyer',
            'nonce' => 'ghi789',
            'origin' => 'http://localhost:5173',
        ], JSON_THROW_ON_ERROR));

        $response = $this->get('/auth/google/callback?state='.urlencode((string) $state));

        $response->assertRedirect('http://localhost:5173/login?social_error=auth_failed');
        $this->assertGuest();
    }

    private function mockSocialiteProvider(string $email, string $name, string $id): Provider
    {
        $socialUser = Mockery::mock(SocialiteUser::class);
        $socialUser->shouldReceive('getEmail')->andReturn($email);
        $socialUser->shouldReceive('getName')->andReturn($name);
        $socialUser->shouldReceive('getNickname')->andReturn(null);
        $socialUser->shouldReceive('getId')->andReturn($id);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('stateless')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        return $provider;
    }
}
