<?php

namespace Tests\Feature;

use App\Models\Property;
use App\Models\SellerPublishFee;
use App\Models\SellerSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerMonetizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_cannot_publish_without_active_subscription(): void
    {
        $seller = User::factory()->seller()->create();
        Sanctum::actingAs($seller);

        $response = $this->postJson('/api/properties', [
            'title' => 'Subscription Required Listing',
            'description' => 'Cannot publish without active subscription',
            'price_ugx' => 1100000,
            'listing_type' => 'rent',
            'property_type' => 'apartment',
            'district' => 'Kampala',
            'city' => 'Kampala Central',
            'address' => 'Billing lane',
            'status' => 'published',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['status']);

        $this->assertDatabaseCount('seller_publish_fees', 0);
    }

    public function test_seller_can_publish_draft_without_subscription(): void
    {
        $seller = User::factory()->seller()->create();
        Sanctum::actingAs($seller);

        $response = $this->postJson('/api/properties', [
            'title' => 'Draft Listing',
            'description' => 'Draft listing is allowed',
            'price_ugx' => 900000,
            'listing_type' => 'rent',
            'property_type' => 'studio',
            'district' => 'Kampala',
            'city' => 'Kampala',
            'address' => 'Draft lane',
            'status' => 'draft',
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('seller_publish_fees', 0);
    }

    public function test_seller_pays_publish_fee_once_per_property(): void
    {
        $seller = User::factory()->seller()->create();

        SellerSubscription::query()->create([
            'user_id' => $seller->id,
            'plan_code' => 'starter_monthly',
            'amount_ugx' => 39000,
            'currency' => 'UGX',
            'status' => 'active',
            'started_at' => now(),
            'renews_at' => now()->addMonth(),
        ]);

        $property = Property::query()->create([
            'corporation_id' => null,
            'user_id' => $seller->id,
            'title' => 'Republish Listing',
            'description' => 'Used to verify one-time publish fee behavior',
            'price_ugx' => 1300000,
            'listing_type' => 'sale',
            'property_type' => 'house',
            'district' => 'Wakiso',
            'city' => 'Entebbe',
            'address' => 'Republish road',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($seller);

        $this->patchJson('/api/properties/'.$property->id, [
            'status' => 'published',
        ])->assertOk();

        $this->patchJson('/api/properties/'.$property->id, [
            'status' => 'draft',
        ])->assertOk();

        $this->patchJson('/api/properties/'.$property->id, [
            'status' => 'published',
        ])->assertOk();

        $this->assertDatabaseCount('seller_publish_fees', 1);

        $fee = SellerPublishFee::query()->where('property_id', $property->id)->first();
        $this->assertNotNull($fee);
        $this->assertSame('charged', $fee->status);
        $this->assertSame(500, (int) $fee->amount_ugx);
    }

    public function test_admin_can_publish_without_subscription_and_is_fee_exempt(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/properties', [
            'title' => 'Admin Publishing',
            'description' => 'Admin should bypass seller billing checks',
            'price_ugx' => 2100000,
            'listing_type' => 'sale',
            'property_type' => 'villa',
            'district' => 'Kampala',
            'city' => 'Kampala',
            'address' => 'Admin avenue',
            'status' => 'published',
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('seller_publish_fees', 0);
    }

    public function test_pesapal_card_billing_endpoints_and_auth_me_fields(): void
    {
        config()->set('services.pesapal.base_url', 'https://pay.pesapal.test/v3');
        config()->set('services.pesapal.consumer_key', 'ck_test_123');
        config()->set('services.pesapal.consumer_secret', 'cs_test_123');
        config()->set('services.pesapal.notification_id', 'ipn_test_123');
        config()->set('services.pesapal.webhook_secret', 'pesapal_whsec_test_123');
        config()->set('services.pesapal.callback_url', 'https://app.verbeek-ug-real-estates.test/api/callbacks/pesapal');

        Http::fake([
            'https://pay.pesapal.test/v3/api/Auth/RequestToken' => Http::response([
                'token' => 'pesapal_token_123',
            ], 200),
            'https://pay.pesapal.test/v3/api/Transactions/SubmitOrderRequest' => Http::response([
                'order_tracking_id' => 'PESAPAL_TRACK_123',
                'merchant_reference' => 'sub_ref_123',
                'redirect_url' => 'https://pay.pesapal.com/redirect/PESAPAL_TRACK_123',
                'payment_status' => 'pending',
            ], 200),
        ]);

        $seller = User::factory()->seller()->create();
        Sanctum::actingAs($seller);

        $this->getJson('/api/seller/billing/status')
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', false)
            ->assertJsonPath('seller_subscription_status', 'inactive');

        $this->postJson('/api/seller/billing/subscribe', [
            'plan_code' => 'growth_monthly',
            'amount_ugx' => 99000,
            'currency' => 'UGX',
            'payment_method' => 'card',
            'billing_email' => 'seller@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', false)
            ->assertJsonPath('seller_subscription_status', 'inactive')
            ->assertJsonPath('subscription.plan_code', 'growth_monthly')
            ->assertJsonPath('checkout.session_id', 'PESAPAL_TRACK_123')
            ->assertJsonPath('checkout.url', 'https://pay.pesapal.com/redirect/PESAPAL_TRACK_123')
            ->assertJsonPath('checkout.selected_payment_method', 'card')
            ->assertJsonPath('checkout.provider', 'pesapal')
            ->assertJsonPath('payment_status', 'pending');

        $payload = json_encode([
            'event_id' => 'pesapal_evt_paid_123',
            'event_type' => 'payment.succeeded',
            'data' => [
                'subscription_id' => (string) SellerSubscription::query()->where('user_id', $seller->id)->value('id'),
                'provider_reference' => 'sub_ref_123',
                'transaction_id' => 'PESAPAL_TRACK_123',
                'channel' => 'CARD',
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $payload, 'pesapal_whsec_test_123');

        $this->call('POST', '/api/webhooks/pesapal', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Pesapal-Signature' => $signature,
        ], $payload)
            ->assertOk()
            ->assertJsonPath('received', true);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', true)
            ->assertJsonPath('seller_subscription_status', 'active');

        $this->postJson('/api/seller/billing/cancel')
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', false)
            ->assertJsonPath('seller_subscription_status', 'inactive');

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', false)
            ->assertJsonPath('seller_subscription_status', 'inactive');
    }

    public function test_pesapal_mobile_money_subscribe_and_webhook_activation_flow(): void
    {
        config()->set('services.pesapal.base_url', 'https://pay.pesapal.test/v3');
        config()->set('services.pesapal.consumer_key', 'ck_test_123');
        config()->set('services.pesapal.consumer_secret', 'cs_test_123');
        config()->set('services.pesapal.notification_id', 'ipn_test_123');
        config()->set('services.pesapal.webhook_secret', 'pesapal_whsec_test_123');
        config()->set('services.pesapal.callback_url', 'https://app.verbeek-ug-real-estates.test/api/callbacks/pesapal');

        Http::fake([
            'https://pay.pesapal.test/v3/api/Auth/RequestToken' => Http::response([
                'token' => 'pesapal_token_123',
            ], 200),
            'https://pay.pesapal.test/v3/api/Transactions/SubmitOrderRequest' => Http::response([
                'order_tracking_id' => 'PESAPAL_MM_TRACK_123',
                'merchant_reference' => 'sub_mm_ref_123',
                'redirect_url' => 'https://pay.pesapal.com/redirect/PESAPAL_MM_TRACK_123',
                'payment_status' => 'pending',
            ], 200),
        ]);

        $seller = User::factory()->seller()->create();
        Sanctum::actingAs($seller);

        $subscribeResponse = $this->postJson('/api/seller/billing/subscribe', [
            'plan_code' => 'growth_monthly',
            'amount_ugx' => 99000,
            'currency' => 'UGX',
            'payment_method' => 'mobile_money',
            'billing_email' => 'seller@example.com',
        ]);

        $subscribeResponse
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', false)
            ->assertJsonPath('seller_subscription_status', 'inactive')
            ->assertJsonPath('checkout.selected_payment_method', 'mobile_money')
            ->assertJsonPath('checkout.provider', 'pesapal')
            ->assertJsonPath('checkout.payment_status', 'pending');

        $subscriptionId = (int) SellerSubscription::query()->where('user_id', $seller->id)->value('id');
        $reference = (string) SellerSubscription::query()->where('id', $subscriptionId)->value('provider_reference');

        $payload = json_encode([
            'event_id' => 'pesapal_evt_paid_mm_123',
            'event_type' => 'payment.succeeded',
            'data' => [
                'subscription_id' => $subscriptionId,
                'provider_reference' => $reference !== '' ? $reference : 'sub_mm_ref_123',
                'transaction_id' => 'PESAPAL_MM_TRACK_123',
                'channel' => 'MOBILE',
            ],
        ], JSON_THROW_ON_ERROR);

        $signature = hash_hmac('sha256', $payload, 'pesapal_whsec_test_123');

        $this->call('POST', '/api/webhooks/pesapal', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Pesapal-Signature' => $signature,
        ], $payload)
            ->assertOk()
            ->assertJsonPath('received', true);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', true)
            ->assertJsonPath('seller_subscription_status', 'active');

        $this->assertDatabaseHas('seller_subscriptions', [
            'id' => $subscriptionId,
            'provider' => 'pesapal',
            'provider_transaction_id' => 'PESAPAL_MM_TRACK_123',
            'provider_last_event_id' => 'pesapal_evt_paid_mm_123',
            'status' => 'active',
        ]);
    }

    public function test_seller_billing_subscribe_rejects_bank_transfer(): void
    {
        config()->set('services.pesapal.consumer_key', 'ck_test_123');

        $seller = User::factory()->seller()->create();
        Sanctum::actingAs($seller);

        $response = $this->postJson('/api/seller/billing/subscribe', [
            'plan_code' => 'growth_monthly',
            'amount_ugx' => 99000,
            'currency' => 'UGX',
            'payment_method' => 'bank_transfer',
            'billing_email' => 'seller@example.com',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['payment_method']);
    }

    public function test_pesapal_webhook_rejects_invalid_signature(): void
    {
        config()->set('services.pesapal.webhook_secret', 'pesapal_whsec_test_123');

        $response = $this->postJson('/api/webhooks/pesapal', [
            'event_id' => 'pesapal_evt_invalid_sig',
            'event_type' => 'payment.succeeded',
            'data' => [
                'transaction_id' => 'PESAPAL_TRACK_123',
            ],
        ], [
            'X-Pesapal-Signature' => 'invalid',
        ]);

        $response->assertStatus(400);
    }

    public function test_pesapal_callback_redirects_to_onboarding_when_payload_is_invalid(): void
    {
        config()->set('services.pesapal.base_url', 'https://pay.pesapal.test/v3');
        config()->set('services.pesapal.consumer_key', 'ck_test_123');
        config()->set('services.pesapal.consumer_secret', 'cs_test_123');

        Http::fake();

        $response = $this->getJson('/api/callbacks/pesapal');

        $response->assertStatus(302);
        $response->assertRedirect('/seller/onboarding?billing_result=pending');
    }

    public function test_pending_subscription_is_marked_failed_when_pesapal_status_is_invalid(): void
    {
        config()->set('services.pesapal.non_production_mock_payment_status', null);
        config()->set('services.pesapal.base_url', 'https://pay.pesapal.test/v3');
        config()->set('services.pesapal.consumer_key', 'ck_test_123');
        config()->set('services.pesapal.consumer_secret', 'cs_test_123');

        $seller = User::factory()->seller()->create();

        SellerSubscription::query()->create([
            'user_id' => $seller->id,
            'provider' => 'pesapal',
            'plan_code' => 'starter_monthly',
            'amount_ugx' => 39000,
            'currency' => 'UGX',
            'payment_method' => 'mobile_money',
            'provider_reference' => 'sub_ref_invalid_123',
            'provider_transaction_id' => 'TRACK_INVALID_123',
            'checkout_session_id' => 'TRACK_INVALID_123',
            'status' => 'inactive',
            'payment_status' => 'pending',
            'started_at' => now(),
            'renews_at' => now()->addMonth(),
        ]);

        Http::fake([
            'https://pay.pesapal.test/v3/api/Auth/RequestToken' => Http::response([
                'token' => 'pesapal_token_123',
            ], 200),
            'https://pay.pesapal.test/v3/api/Transactions/GetTransactionStatus*' => Http::response([
                'payment_status_description' => 'INVALID',
                'merchant_reference' => 'sub_ref_invalid_123',
                'order_tracking_id' => 'TRACK_INVALID_123',
            ], 200),
        ]);

        Sanctum::actingAs($seller);

        $this->getJson('/api/seller/billing/status')
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', false)
            ->assertJsonPath('seller_subscription_status', 'past_due');

        $this->assertDatabaseHas('seller_subscriptions', [
            'user_id' => $seller->id,
            'status' => 'past_due',
            'payment_status' => 'failed',
        ]);
    }

    public function test_pending_subscription_is_marked_failed_when_pesapal_status_lookup_returns_not_found(): void
    {
        config()->set('services.pesapal.non_production_mock_payment_status', null);
        config()->set('services.pesapal.base_url', 'https://pay.pesapal.test/v3');
        config()->set('services.pesapal.consumer_key', 'ck_test_123');
        config()->set('services.pesapal.consumer_secret', 'cs_test_123');

        $seller = User::factory()->seller()->create();

        SellerSubscription::query()->create([
            'user_id' => $seller->id,
            'provider' => 'pesapal',
            'plan_code' => 'starter_monthly',
            'amount_ugx' => 39000,
            'currency' => 'UGX',
            'payment_method' => 'mobile_money',
            'provider_reference' => 'sub_ref_not_found_123',
            'provider_transaction_id' => '',
            'checkout_session_id' => '',
            'status' => 'inactive',
            'payment_status' => 'pending',
            'started_at' => now(),
            'renews_at' => now()->addMonth(),
        ]);

        Http::fake([
            'https://pay.pesapal.test/v3/api/Auth/RequestToken' => Http::response([
                'token' => 'pesapal_token_123',
            ], 200),
            'https://pay.pesapal.test/v3/api/Transactions/GetTransactionStatus*' => Http::response([
                'error' => [
                    'code' => 'payment_details_not_found',
                ],
            ], 404),
        ]);

        Sanctum::actingAs($seller);

        $this->getJson('/api/seller/billing/status')
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', false)
            ->assertJsonPath('seller_subscription_status', 'past_due');

        $this->assertDatabaseHas('seller_subscriptions', [
            'user_id' => $seller->id,
            'status' => 'past_due',
            'payment_status' => 'failed',
        ]);
    }

    public function test_non_production_mock_status_can_force_subscription_paid(): void
    {
        config()->set('services.pesapal.non_production_mock_payment_status', 'paid');

        $seller = User::factory()->seller()->create();

        SellerSubscription::query()->create([
            'user_id' => $seller->id,
            'provider' => 'pesapal',
            'plan_code' => 'starter_monthly',
            'amount_ugx' => 39000,
            'currency' => 'UGX',
            'payment_method' => 'mobile_money',
            'provider_reference' => 'sub_ref_mock_paid_123',
            'provider_transaction_id' => 'TRACK_MOCK_PAID_123',
            'checkout_session_id' => 'TRACK_MOCK_PAID_123',
            'status' => 'inactive',
            'payment_status' => 'pending',
            'started_at' => now(),
            'renews_at' => now()->addMonth(),
        ]);

        Sanctum::actingAs($seller);

        $this->getJson('/api/seller/billing/status')
            ->assertOk()
            ->assertJsonPath('seller_has_active_subscription', true)
            ->assertJsonPath('seller_subscription_status', 'active');

        $this->assertDatabaseHas('seller_subscriptions', [
            'user_id' => $seller->id,
            'status' => 'active',
            'payment_status' => 'paid',
        ]);
    }

    public function test_non_production_mock_status_can_force_publish_fee_paid_on_callback(): void
    {
        config()->set('services.pesapal.non_production_mock_payment_status', 'paid');

        $seller = User::factory()->seller()->create();
        Sanctum::actingAs($seller);

        SellerSubscription::query()->create([
            'user_id' => $seller->id,
            'provider' => 'pesapal',
            'plan_code' => 'starter_monthly',
            'amount_ugx' => 39000,
            'currency' => 'UGX',
            'payment_method' => 'mobile_money',
            'status' => 'active',
            'payment_status' => 'paid',
            'started_at' => now(),
            'renews_at' => now()->addMonth(),
        ]);

        $property = Property::query()->create([
            'corporation_id' => null,
            'user_id' => $seller->id,
            'title' => 'Mock Publish Fee Listing',
            'description' => 'Listing for non-production publish fee mock status test.',
            'price_ugx' => 1300000,
            'listing_type' => 'sale',
            'property_type' => 'house',
            'district' => 'Wakiso',
            'city' => 'Entebbe',
            'address' => 'Mock fee road',
            'status' => 'draft',
        ]);

        SellerPublishFee::query()->create([
            'user_id' => $seller->id,
            'property_id' => $property->id,
            'provider' => 'pesapal',
            'amount_ugx' => 500,
            'currency' => 'UGX',
            'payment_method' => 'mobile_money',
            'checkout_session_id' => 'TRACK_PUB_MOCK_PAID_123',
            'provider_transaction_id' => 'TRACK_PUB_MOCK_PAID_123',
            'reference' => 'pub_mock_paid_123',
            'status' => 'failed',
            'payment_status' => 'pending',
        ]);

        $this->getJson('/api/callbacks/pesapal?merchant_reference=pub_mock_paid_123&order_tracking_id=TRACK_PUB_MOCK_PAID_123')
            ->assertStatus(302)
            ->assertRedirect('/?owned=1&created=1');

        $this->assertDatabaseHas('seller_publish_fees', [
            'user_id' => $seller->id,
            'property_id' => $property->id,
            'status' => 'charged',
            'payment_status' => 'paid',
        ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'status' => 'published',
        ]);
    }
}
