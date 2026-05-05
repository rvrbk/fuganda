<?php

namespace App\Services;

use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PropertyService
{
    public function createForUser(User $user, array $attributes): Property
    {
        return DB::transaction(function () use ($user, $attributes): Property {
            $data = Arr::except($attributes, ['images']);
            $data['corporation_id'] = $user->corporation_id;
            $data['user_id'] = $user->id;

            if (($data['status'] ?? null) === 'published' && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $property = Property::query()->create($data);
            $this->syncImages($property, $attributes['images'] ?? []);

            return $property->load(['images', 'user:id,name']);
        });
    }

    public function updateForUser(User $user, Property $property, array $attributes): Property
    {
        $this->authorizeOwnership($user, $property);

        return DB::transaction(function () use ($attributes, $property): Property {
            $data = Arr::except($attributes, ['images']);

            if (($data['status'] ?? null) === 'published' && empty($property->published_at) && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            if (array_key_exists('status', $data) && $data['status'] !== 'published') {
                $data['published_at'] = null;
            }

            $property->fill($data);
            $property->save();

            if (array_key_exists('images', $attributes)) {
                $this->syncImages($property, $attributes['images'] ?? []);
            }

            return $property->load(['images', 'user:id,name']);
        });
    }

    public function deleteForUser(User $user, Property $property): void
    {
        $this->authorizeOwnership($user, $property);

        $property->delete();
    }

    private function authorizeOwnership(User $user, Property $property): void
    {
        if ((int) $property->corporation_id !== (int) $user->corporation_id) {
            throw new AuthorizationException('You cannot manage properties outside your tenant.');
        }

        if ((int) $property->user_id !== (int) $user->id) {
            throw new AuthorizationException('Only the listing owner can modify this property.');
        }
    }

    private function syncImages(Property $property, array $images): void
    {
        $property->images()->delete();

        foreach ($images as $index => $image) {
            $property->images()->create([
                'path' => $image['path'],
                'sort_order' => $image['sort_order'] ?? $index,
            ]);
        }
    }
}
