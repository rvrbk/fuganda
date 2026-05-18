<?php

namespace App\Services;

use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertySearchService
{
    public function search(array $filters, bool $publishedOnly = true): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = Property::query()
            ->with(['images', 'user:id,name']);

        if ($publishedOnly) {
            $query->published();
        }

        if (! empty($filters['location'])) {
            $location = trim((string) $filters['location']);
            $query->where(function ($builder) use ($location): void {
                $builder
                    ->where('district', 'like', "%{$location}%")
                    ->orWhere('city', 'like', "%{$location}%")
                    ->orWhere('address', 'like', "%{$location}%");
            });
        }

        if (! empty($filters['district'])) {
            $query->where('district', $filters['district']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['listing_type'])) {
            $query->where('listing_type', $filters['listing_type']);
        }

        if (! empty($filters['property_type'])) {
            $query->where('property_type', $filters['property_type']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (array_key_exists('bedrooms', $filters) && $filters['bedrooms'] !== null) {
            $query->where('bedrooms', '>=', (int) $filters['bedrooms']);
        }

        if (array_key_exists('bathrooms', $filters) && $filters['bathrooms'] !== null) {
            $query->where('bathrooms', '>=', (int) $filters['bathrooms']);
        }

        if (array_key_exists('min_price', $filters) && $filters['min_price'] !== null) {
            $query->where('price_ugx', '>=', (int) $filters['min_price']);
        }

        if (array_key_exists('max_price', $filters) && $filters['max_price'] !== null) {
            $query->where('price_ugx', '<=', (int) $filters['max_price']);
        }

        return $query
            ->latest()
            ->paginate(max(1, min($perPage, 100)))
            ->withQueryString();
    }
}
