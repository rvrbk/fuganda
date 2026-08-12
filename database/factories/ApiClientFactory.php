<?php

namespace Database\Factories;

use App\Models\ApiClient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiClient>
 */
class ApiClientFactory extends Factory
{
    protected $model = ApiClient::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' API Client',
            'uuid' => ApiClient::generateUuid(),
            'client_id' => ApiClient::generateClientId(),
            'client_secret' => ApiClient::generateClientSecret(),
            'password_client' => true,
            'personal_access_client' => false,
            'revoked' => false,
            'scopes' => ['*'],
        ];
    }

    public function passwordClient(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'password_client' => true,
            ];
        });
    }

    public function revoked(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'revoked' => true,
            ];
        });
    }

    public function withScopes(array $scopes): static
    {
        return $this->state(function (array $attributes) use ($scopes) {
            return [
                'scopes' => $scopes,
            ];
        });
    }
}
