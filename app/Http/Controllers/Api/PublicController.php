<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Corporation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'service' => config('app.name'),
        ]);
    }

    public function tenantByDomain(Request $request): JsonResponse
    {
        $host = strtolower((string) $request->getHost());

        $tenant = Corporation::query()->where('domain', $host)->first();

        return response()->json([
            'host' => $host,
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
            ] : null,
        ]);
    }
}
