<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'role', 'password', 'corporation_id', 'oauth_provider', 'oauth_provider_id', 'email_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function corporation(): BelongsTo
    {
        return $this->belongsTo(Corporation::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function sellerSubscription(): HasOne
    {
        return $this->hasOne(SellerSubscription::class)->ofMany('id', 'max');
    }

    public function sellerPublishFees(): HasMany
    {
        return $this->hasMany(SellerPublishFee::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isBuyer(): bool
    {
        return $this->role === 'buyer';
    }

    public function hasActiveSellerSubscription(): bool
    {
        if (! $this->isSeller()) {
            return false;
        }

        $subscription = SellerSubscription::query()
            ->where('user_id', $this->id)
            ->latest('id')
            ->first();

        return $subscription?->status === 'active';
    }

    public function sellerSubscriptionStatus(): string
    {
        if (! $this->isSeller()) {
            return 'not_applicable';
        }

        $subscription = SellerSubscription::query()
            ->where('user_id', $this->id)
            ->latest('id')
            ->first();

        return $subscription?->status ?? 'inactive';
    }
}
