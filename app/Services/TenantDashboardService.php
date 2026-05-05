<?php

namespace App\Services;

use App\Models\User;

class TenantDashboardService
{
    public function buildFor(User $user): array
    {
        $tenant = $user->corporation;

        return [
            'tenant' => [
                'id' => $tenant?->id,
                'name' => $tenant?->name,
                'domain' => $tenant?->domain,
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }
}
