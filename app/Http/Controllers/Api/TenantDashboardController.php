<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TenantDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantDashboardController extends Controller
{
    public function __construct(private readonly TenantDashboardService $dashboardService)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json(
            $this->dashboardService->buildFor($request->user())
        );
    }
}
