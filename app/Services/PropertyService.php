<?php

namespace App\Services;

use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PropertyService
{
    public function __construct(private readonly SellerBillingService $sellerBillingService)
    {
    }

    public function createForUser(User $user, array $attributes): Property
    {
        return DB::transaction(function () use ($user, $attributes): Property {
            $data = Arr::except($attributes, ['images']);
            $data['corporation_id'] = $user->corporation_id;
            $data['user_id'] = $user->id;

            $isPublishing = ($data['status'] ?? null) === 'published';
            $publishFeeCheckoutUrl = null;
            $publishFeePaymentRequired = false;
            $isSellerPublish = $isPublishing && $user->isSeller() && ! $user->isAdmin();

            if ($isPublishing) {
                $this->sellerBillingService->enforcePublishRequirements($user);
            }

            if ($isSellerPublish) {
                // Require publish-fee settlement before switching listing to published.
                $data['status'] = 'draft';
                $data['published_at'] = null;
            } elseif ($isPublishing && empty($data['published_at'])) {
                $data['published_at'] = now();
            }

            $property = Property::query()->create($data);
            $this->syncImages($property, $attributes['images'] ?? []);

            if ($isSellerPublish) {
                $publishFee = $this->sellerBillingService->requestPublishFeeCheckout($user, $property, $attributes);
                if (! ($publishFee['paid'] ?? false)) {
                    $publishFeeCheckoutUrl = (string) ($publishFee['checkout_url'] ?? '');
                    $publishFeePaymentRequired = true;
                } else {
                    $property->fill([
                        'status' => 'published',
                        'published_at' => now(),
                    ]);
                    $property->save();
                }
            }

            $loadedProperty = $property->load(['images', 'user:id,name']);
            if ($publishFeePaymentRequired) {
                $loadedProperty->setAttribute('publish_fee_payment_required', true);
                $loadedProperty->setAttribute('publish_fee_checkout_url', $publishFeeCheckoutUrl);
            }

            return $loadedProperty;
        });
    }

    public function updateForUser(User $user, Property $property, array $attributes): Property
    {
        $this->authorizeOwnership($user, $property);

        return DB::transaction(function () use ($user, $attributes, $property): Property {
            $data = Arr::except($attributes, ['images']);

            $isPublishing = ($data['status'] ?? null) === 'published';
            $publishFeeCheckoutUrl = null;
            $publishFeePaymentRequired = false;
            $isSellerPublish = $isPublishing && $user->isSeller() && ! $user->isAdmin();

            if ($isPublishing) {
                $this->sellerBillingService->enforcePublishRequirements($user);
            }

            if ($isSellerPublish) {
                $publishFee = $this->sellerBillingService->requestPublishFeeCheckout($user, $property, $attributes);
                if (! ($publishFee['paid'] ?? false)) {
                    $publishFeeCheckoutUrl = (string) ($publishFee['checkout_url'] ?? '');
                    $publishFeePaymentRequired = true;
                    $data['status'] = 'draft';
                    $data['published_at'] = null;
                }
            }

            if ($isPublishing && ! $publishFeePaymentRequired && empty($property->published_at) && empty($data['published_at'])) {
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

            $loadedProperty = $property->load(['images', 'user:id,name']);
            if ($publishFeePaymentRequired) {
                $loadedProperty->setAttribute('publish_fee_payment_required', true);
                $loadedProperty->setAttribute('publish_fee_checkout_url', $publishFeeCheckoutUrl);
            }

            return $loadedProperty;
        });
    }

    public function deleteForUser(User $user, Property $property): void
    {
        $this->authorizeOwnership($user, $property);

        $property->delete();
    }

    private function authorizeOwnership(User $user, Property $property): void
    {
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
