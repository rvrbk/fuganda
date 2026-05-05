<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    public function index(): JsonResponse
    {
        $locations = Location::query()
            ->where('is_active', true)
            ->orderBy('country')
            ->orderBy('district')
            ->orderBy('city')
            ->get();

        return response()->json($locations);
    }
}
