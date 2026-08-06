<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyIndexRequest;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\PersonalAccessToken;
use App\Models\Property;
use App\Services\PropertySearchService;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertySearchService $searchService,
        private readonly PropertyService $propertyService,
    ) {
    }

    public function index(PropertyIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();

        if ($request->boolean('owned') && $request->user() !== null) {
            $filters['user_id'] = $request->user()->id;
            $filters['include_hidden'] = true;
        }

        $publishedOnly = ! ($request->boolean('owned') && $request->user() !== null);

        $properties = $this->searchService->search($filters, $publishedOnly);

        return response()->json($properties);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $property = Property::query()
            ->with(['images', 'user:id,name'])
            ->findOrFail($id);

        // Try to get authenticated user (works even without auth middleware if token is present)
        $user = $request->user();
        
        // If no user from request, try to authenticate from token
        if ($user === null) {
            $token = $request->bearerToken();
            if ($token) {
                $personalAccessToken = \App\Models\PersonalAccessToken::findToken($token);
                if ($personalAccessToken) {
                    $user = $personalAccessToken->tokenable;
                }
            }
        }

        // Allow viewing unpublished/invisible properties if user is authenticated and owns them
        if ($user !== null && (int) $property->user_id === (int) $user->id) {
            // Owner can view their own properties regardless of status/visibility
            return response()->json($property);
        }

        // For non-owners or unauthenticated users, only show published and visible properties
        if ($property->status !== 'published' || ! $property->is_visible) {
            abort(404);
        }

        return response()->json($property);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->propertyService->createForUser(
            $request->user(),
            $request->validated()
        );

        return response()->json($property, 201);
    }

    public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
    {
        $updatedProperty = $this->propertyService->updateForUser(
            $request->user(),
            $property,
            $request->validated()
        );

        return response()->json($updatedProperty);
    }

    public function destroy(Request $request, Property $property): Response
    {
        $this->propertyService->deleteForUser($request->user(), $property);

        return response()->noContent();
    }
}
