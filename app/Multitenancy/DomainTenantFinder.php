<?php

namespace App\Multitenancy;

use App\Models\Corporation;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Models\Concerns\UsesLandlordConnection;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

class DomainTenantFinder extends TenantFinder
{
    use UsesLandlordConnection;

    public function findForRequest(Request $request): ?Corporation
    {
        $host = strtolower((string) $request->getHost());

        if ($host === '' || $host === 'localhost' || $host === '127.0.0.1') {
            return null;
        }

        return Corporation::query()
            ->where('domain', $host)
            ->first();
    }
}
