<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PropertyIndexRequest;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
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
        }

        $properties = $this->searchService->search($filters, true);

        return response()->json($properties);
    }

    public function show(int $id): JsonResponse
    {
        $property = Property::query()
            ->with(['images', 'user:id,name'])
            ->published()
            ->findOrFail($id);

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
