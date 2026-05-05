<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    protected $fillable = [
        'corporation_id',
        'user_id',
        'title',
        'description',
        'price_ugx',
        'listing_type',
        'property_type',
        'bedrooms',
        'bathrooms',
        'district',
        'city',
        'address',
        'latitude',
        'longitude',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'price_ugx' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
            'published_at' => 'datetime',
        ];
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
