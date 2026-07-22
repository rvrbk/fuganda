<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Facades\Socialite;
use JsonException;
use Throwable;

class SocialAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'apple'];
    private const ALLOWED_ROLES = ['buyer', 'seller'];

    public function redirect(string $provider): RedirectResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404);
        }

        $requestedRole = strtolower((string) request('role', 'buyer'));
        if (! in_array($requestedRole, self::ALLOWED_ROLES, true)) {
            return redirect('/login?social_error=invalid_role');
        }

        $statePayload = [
            'role' => $requestedRole,
            'nonce' => Str::random(24),
            'origin' => request()->getSchemeAndHttpHost(),
        ];

        $state = base64_encode(json_encode($statePayload, JSON_THROW_ON_ERROR));

        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);
        $driver = $driver->stateless();

        return $driver
            ->with(['state' => $state])
            ->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            abort(404);
        }

        $state = $this->decodeStateFromRequest();
        $origin = $this->resolveTrustedOrigin($state['origin'] ?? null);

        try {
            /** @var AbstractProvider $driver */
            $driver = Socialite::driver($provider);
            $driver = $driver->stateless();

            $socialUser = $driver->user();
        } catch (Throwable) {
            return redirect()->away($origin.'/login?social_error=auth_failed');
        }

        $email = trim((string) ($socialUser->getEmail() ?? ''));
        if ($email === '') {
            return redirect()->away($origin.'/login?social_error=email_required');
        }

        $user = User::query()->where('email', $email)->first();
        if ($user && ! in_array((string) $user->role, self::ALLOWED_ROLES, true)) {
            return redirect()->away($origin.'/login?social_error=role_not_allowed');
        }

        $requestedRole = 'buyer';
        try {
            $stateRole = strtolower((string) ($state['role'] ?? 'buyer'));
            if (in_array($stateRole, self::ALLOWED_ROLES, true)) {
                $requestedRole = $stateRole;
            }
        } catch (JsonException|Throwable) {
            $requestedRole = 'buyer';
        }

        if (! in_array($requestedRole, self::ALLOWED_ROLES, true)) {
            $requestedRole = 'buyer';
        }

        if (! $user) {
            $user = User::query()->create([
                'name' => trim((string) ($socialUser->getName() ?: $socialUser->getNickname() ?: 'Buyer')),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => $requestedRole,
                'corporation_id' => null,
                'email_verified_at' => now(),
                'oauth_provider' => $provider,
                'oauth_provider_id' => (string) $socialUser->getId(),
            ]);
        } else {
            $user->forceFill([
                'oauth_provider' => $provider,
                'oauth_provider_id' => (string) $socialUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        }

        Auth::login($user, true);
        request()->session()->regenerate();

        return redirect()->away($origin.'/');
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeStateFromRequest(): array
    {
        try {
            $rawState = (string) request()->query('state', '');
            if ($rawState === '') {
                return [];
            }

            $decoded = base64_decode($rawState, true);
            if ($decoded === false) {
                return [];
            }

            $payload = json_decode($decoded, true, flags: JSON_THROW_ON_ERROR);

            return is_array($payload) ? $payload : [];
        } catch (JsonException|Throwable) {
            return [];
        }
    }

    private function resolveTrustedOrigin(mixed $origin): string
    {
        $fallback = rtrim((string) request()->getSchemeAndHttpHost(), '/');
        $requestHost = strtolower((string) request()->getHost());
        $candidate = is_string($origin) ? trim($origin) : '';

        if ($candidate === '') {
            return $fallback;
        }

        $parts = parse_url($candidate);
        if (! is_array($parts)) {
            return $fallback;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = $parts['port'] ?? null;

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return $fallback;
        }

        $allowedHosts = [];
        foreach ((array) config('sanctum.stateful', []) as $domain) {
            $domain = strtolower(trim((string) $domain));
            if ($domain !== '') {
                $allowedHosts[] = $domain;
            }
        }

        $appUrlHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (is_string($appUrlHost) && $appUrlHost !== '') {
            $allowedHosts[] = strtolower($appUrlHost);
        }

        $hostWithPort = $host.($port ? ':'.$port : '');
        if (! in_array($host, $allowedHosts, true) && ! in_array($hostWithPort, $allowedHosts, true)) {
            return $fallback;
        }

        // Never cross hosts on callback completion; keep users on the callback host.
        if ($requestHost !== '' && $host !== $requestHost) {
            return $fallback;
        }

        return rtrim($scheme.'://'.$hostWithPort, '/');
    }
}
