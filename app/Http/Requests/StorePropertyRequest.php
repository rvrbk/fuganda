<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // In demo mode, allow anyone to create properties
        if (config('app.demo_mode')) {
            return $this->user() !== null;
        }

        $user = $this->user();

        return $user !== null && ($user->isSeller() || $user->isAdmin());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price_ugx' => ['required', 'integer', 'min:0'],
            'price_currency' => ['sometimes', 'in:UGX,USD'],
            'listing_type' => ['required', 'in:rent,sale'],
            'property_type' => ['required', 'string', 'max:120'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'district' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['sometimes', 'in:draft,published,archived'],
            'published_at' => ['nullable', 'date'],
            'images' => ['sometimes', 'array'],
            'images.*.path' => ['required_with:images', 'string', 'max:2048'],
            'images.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
