<?php

namespace Tests\Feature;

use App\Mail\SellerBillingPaymentMail;
use App\Models\Property;
use App\Models\SellerPublishFee;
use App\Models\SellerSubscription;
use App\Models\User;
use App\Services\SellerBillingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SellerBillingEmailsAndGraceTest extends TestCase
{
    use RefreshDatabase;

    // ────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────

    private function webhookSecret(): string
    {
        return 'pesapal_whsec_test';
    }

    private function configureWebhookSecret(): void
    {
        config()->set('services.pesapal.webhook_secret', $this->webhookSecret());
    }

    private function configureGracePeriod(int $days = 7): void
    {
        config()->set('services.pesapal.subscription_grace_period_days', $days);
    }

    /**
     * Send a Pesapal webhook payload and return the response.
     *
     * @param array<string,mixed> $event
     */
    private function sendWebhook(array $event): \Illuminate\Testing\TestResponse
    {
        $json = json_encode($event, JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $json, $this->webhookSecret());

        return $this->call(
            'POST',
            '/api/webhooks/pesapal',
            [],
            [],
            [],
            [
                'CONTENT_TYPE'              => 'application/json',
                'HTTP_X-Pesapal-Signature'  => $sig,
            ],
            $json
        );
    }

    /**
     * Create an active subscription for the given seller with a controlled renews_at date.
     */
    private function createActiveSubscription(User $seller, Carbon $renewsAt): SellerSubscription
    {
        return SellerSubscription::query()->create([
            'user_id'          => $seller->id,
            'provider'         => 'pesapal',
            'plan_code'        => 'starter_monthly',
            'amount_ugx'       => 39000,
            'currency'         => 'UGX',
            'payment_method'   => 'mobile_money',
            'status'           => 'active',
            'payment_status'   => 'paid',
            'provider_reference' => 'sub_test_ref_'.uniqid(),
            'activated_at'     => now()->subMonth(),
            'started_at'       => now()->subMonth(),
            'renews_at'        => $renewsAt,
        ]);
    }

    /**
     * Create a pending subscription (checkout started, not yet paid).
     */
    private function createPendingSubscription(User $seller, string $reference, string $transactionId): SellerSubscription
    {
        return SellerSubscription::query()->create([
            'user_id'                  => $seller->id,
            'provider'                 => 'pesapal',
            'plan_code'                => 'starter_monthly',
            'amount_ugx'               => 39000,
            'currency'                 => 'UGX',
            'payment_method'           => 'mobile_money',
            'status'                   => 'inactive',
            'payment_status'           => 'pending',
            'provider_reference'       => $reference,
            'provider_transaction_id'  => $transactionId,
            'checkout_session_id'      => $transactionId,
            'started_at'               => now(),
            'renews_at'                => now()->addMonth(),
        ]);
    }

    // ────────────────────────────────────────────────────────────────
    // Webhook → email: subscription outcomes
    // ────────────────────────────────────────────────────────────────

    public function test_subscription_payment_confirmed_via_webhook_sends_confirmation_email(): void
    {
        Mail::fake();
        $this->configureWebhookSecret();

        $seller = User::factory()->seller()->create();
        $sub = $this->createPendingSubscription($seller, 'sub_ref_paid_001', 'TRK_PAID_001');

        $this->sendWebhook([
            'event_id'   => 'evt_paid_001',
            'event_type' => 'payment.succeeded',
            'data'       => [
                'subscription_id'   => (string) $sub->id,
                'provider_reference' => 'sub_ref_paid_001',
                'transaction_id'    => 'TRK_PAID_001',
                'channel'           => 'MOBILE',
            ],
        ])->assertOk();

        Mail::assertSent(SellerBillingPaymentMail::class, function (SellerBillingPaymentMail $mail) use ($seller) {
            return $mail->subjectLine === 'Subscription payment confirmed'
                && $mail->hasTo($seller->email);
        });

        $this->assertDatabaseHas('seller_subscriptions', [
            'id'             => $sub->id,
            'status'         => 'active',
            'payment_status' => 'paid',
        ]);
    }

    public function test_subscription_payment_failed_via_webhook_sends_failure_email_with_retry_link(): void
    {
        Mail::fake();
        $this->configureWebhookSecret();

        $seller = User::factory()->seller()->create();
        $sub = $this->createPendingSubscription($seller, 'sub_ref_fail_001', 'TRK_FAIL_001');

        $this->sendWebhook([
            'event_id'   => 'evt_fail_001',
            'event_type' => 'payment.failed',
            'data'       => [
                'subscription_id'    => (string) $sub->id,
                'provider_reference' => 'sub_ref_fail_001',
                'transaction_id'     => 'TRK_FAIL_001',
                'channel'            => 'MOBILE',
            ],
        ])->assertOk();

        Mail::assertSent(SellerBillingPaymentMail::class, function (SellerBillingPaymentMail $mail) use ($seller) {
            return $mail->subjectLine === 'Subscription payment failed'
                && $mail->hasTo($seller->email)
                && str_contains((string) $mail->actionUrl, '/seller/onboarding');
        });

        $this->assertDatabaseHas('seller_subscriptions', [
            'id'             => $sub->id,
            'status'         => 'past_due',
            'payment_status' => 'failed',
        ]);
    }

    public function test_subscription_payment_declined_maps_to_failure_email(): void
    {
        Mail::fake();
        $this->configureWebhookSecret();

        $seller = User::factory()->seller()->create();
        $sub = $this->createPendingSubscription($seller, 'sub_ref_decl_001', 'TRK_DECL_001');

        $this->sendWebhook([
            'event_id'   => 'evt_decl_001',
            'event_type' => 'payment.invalid',   // Pesapal INVALID maps to "fail" path
            'data'       => [
                'subscription_id'    => (string) $sub->id,
                'provider_reference' => 'sub_ref_decl_001',
                'transaction_id'     => 'TRK_DECL_001',
                'channel'            => 'MOBILE',
            ],
        ])->assertOk();

        Mail::assertSent(SellerBillingPaymentMail::class, function (SellerBillingPaymentMail $mail) {
            return $mail->subjectLine === 'Subscription payment failed';
        });
    }

    public function test_subscription_expired_via_webhook_sends_expiry_email_with_resume_link(): void
    {
        Mail::fake();
        $this->configureWebhookSecret();

        $seller = User::factory()->seller()->create();
        $sub = $this->createPendingSubscription($seller, 'sub_ref_exp_001', 'TRK_EXP_001');

        $this->sendWebhook([
            'event_id'   => 'evt_exp_001',
            'event_type' => 'payment.expired',
            'data'       => [
                'subscription_id'    => (string) $sub->id,
                'provider_reference' => 'sub_ref_exp_001',
                'transaction_id'     => 'TRK_EXP_001',
                'channel'            => 'MOBILE',
            ],
        ])->assertOk();

        Mail::assertSent(SellerBillingPaymentMail::class, function (SellerBillingPaymentMail $mail) use ($seller) {
            return $mail->subjectLine === 'Subscription expired'
                && $mail->hasTo($seller->email)
                && str_contains((string) $mail->actionUrl, '/seller/onboarding');
        });

        $this->assertDatabaseHas('seller_subscriptions', [
            'id'     => $sub->id,
            'status' => 'inactive',
        ]);
    }

    public function test_subscription_cancelled_via_webhook_sends_expiry_email(): void
    {
        Mail::fake();
        $this->configureWebhookSecret();

        $seller = User::factory()->seller()->create();
        $sub = $this->createPendingSubscription($seller, 'sub_ref_cancel_001', 'TRK_CANCEL_001');

        $this->sendWebhook([
            'event_id'   => 'evt_cancel_001',
            'event_type' => 'payment.canceled',
            'data'       => [
                'subscription_id'    => (string) $sub->id,
                'provider_reference' => 'sub_ref_cancel_001',
                'transaction_id'     => 'TRK_CANCEL_001',
                'channel'            => 'MOBILE',
            ],
        ])->assertOk();

        Mail::assertSent(SellerBillingPaymentMail::class, function (SellerBillingPaymentMail $mail) {
            return $mail->subjectLine === 'Subscription expired';
        });
    }

    public function test_duplicate_webhook_event_id_does_not_send_a_second_email(): void
    {
        Mail::fake();
        $this->configureWebhookSecret();

        $seller = User::factory()->seller()->create();
        $sub = $this->createPendingSubscription($seller, 'sub_ref_dup_001', 'TRK_DUP_001');

        $event = [
            'event_id'   => 'evt_dup_001',
            'event_type' => 'payment.succeeded',
            'data'       => [
                'subscription_id'    => (string) $sub->id,
                'provider_reference' => 'sub_ref_dup_001',
                'transaction_id'     => 'TRK_DUP_001',
                'channel'            => 'MOBILE',
            ],
        ];

        $this->sendWebhook($event)->assertOk();
        $this->sendWebhook($event)->assertOk();

        // Exactly one email — the second call is an idempotent no-op
        Mail::assertSentCount(1);
    }

    // ────────────────────────────────────────────────────────────────
    // Grace period: status transitions
    // ────────────────────────────────────────────────────────────────

    public function test_subscription_stays_active_when_inside_grace_period(): void
    {
        $this->configureGracePeriod(7);

        $seller = User::factory()->seller()->create();
        // renews_at is 3 days ago — inside the 7-day grace window
        $this->createActiveSubscription($seller, now()->subDays(3));

        $service = app(SellerBillingService::class);
        $status  = $service->statusFor($seller);

        $this->assertTrue($status['seller_has_active_subscription']);
        $this->assertSame('active', $status['seller_subscription_status']);
    }

    public function test_subscription_becomes_past_due_after_grace_period_expires(): void
    {
        $this->configureGracePeriod(7);

        $seller = User::factory()->seller()->create();
        // renews_at is 9 days ago — 2 days past the 7-day grace boundary
        $this->createActiveSubscription($seller, now()->subDays(9));

        $service = app(SellerBillingService::class);
        $status  = $service->statusFor($seller);

        $this->assertFalse($status['seller_has_active_subscription']);
        $this->assertSame('past_due', $status['seller_subscription_status']);
    }

    public function test_subscription_exactly_at_grace_boundary_is_still_active(): void
    {
        $this->configureGracePeriod(7);

        $seller = User::factory()->seller()->create();
        // renews_at is exactly 7 days ago — boundary is NOT crossed yet (now must be > boundary)
        $this->createActiveSubscription($seller, now()->subDays(7)->addMinute());

        $service = app(SellerBillingService::class);
        $status  = $service->statusFor($seller);

        $this->assertTrue($status['seller_has_active_subscription']);
    }

    public function test_zero_day_grace_period_marks_past_due_immediately_after_renewal_date(): void
    {
        $this->configureGracePeriod(0);

        $seller = User::factory()->seller()->create();
        // renews_at is 1 day ago — grace = 0, so boundary = renews_at itself
        $this->createActiveSubscription($seller, now()->subDay());

        $service = app(SellerBillingService::class);
        $status  = $service->statusFor($seller);

        $this->assertFalse($status['seller_has_active_subscription']);
        $this->assertSame('past_due', $status['seller_subscription_status']);
    }

    // ────────────────────────────────────────────────────────────────
    // billing:send-payment-reminders command
    // ────────────────────────────────────────────────────────────────

    public function test_reminder_command_sends_renewal_email_when_inside_grace_period(): void
    {
        Mail::fake();
        $this->configureGracePeriod(7);

        $seller = User::factory()->seller()->create();
        // renews_at is 3 days ago → past due date but within grace → renewal email
        $this->createActiveSubscription($seller, now()->subDays(3));

        $exitCode = $this->artisan('billing:send-payment-reminders')
            ->assertExitCode(0)
            ->execute();

        Mail::assertSent(SellerBillingPaymentMail::class, function (SellerBillingPaymentMail $mail) use ($seller) {
            return $mail->subjectLine === 'Monthly subscription payment request'
                && $mail->hasTo($seller->email)
                && str_contains((string) $mail->actionUrl, '/seller/onboarding');
        });
    }

    public function test_reminder_command_sends_overdue_email_after_grace_period_expires(): void
    {
        Mail::fake();
        $this->configureGracePeriod(7);

        $seller = User::factory()->seller()->create();
        // renews_at is 9 days ago → past the 7-day grace boundary → overdue email
        $this->createActiveSubscription($seller, now()->subDays(9));

        $this->artisan('billing:send-payment-reminders')->assertExitCode(0)->execute();

        Mail::assertSent(SellerBillingPaymentMail::class, function (SellerBillingPaymentMail $mail) use ($seller) {
            return $mail->subjectLine === 'Subscription payment overdue'
                && $mail->hasTo($seller->email)
                && str_contains((string) $mail->actionUrl, '/seller/onboarding');
        });

        // Subscription is also persisted as past_due
        $this->assertDatabaseHas('seller_subscriptions', [
            'user_id' => $seller->id,
            'status'  => 'past_due',
        ]);
    }

    public function test_reminder_command_output_reports_email_count(): void
    {
        Mail::fake();
        $this->configureGracePeriod(7);

        $sellerA = User::factory()->seller()->create();
        $sellerB = User::factory()->seller()->create();

        $this->createActiveSubscription($sellerA, now()->subDays(3));
        $this->createActiveSubscription($sellerB, now()->subDays(9));

        $this->artisan('billing:send-payment-reminders')
            ->assertExitCode(0)
            ->expectsOutputToContain('emails_sent=2');
    }

    public function test_renewal_email_not_sent_twice_within_same_billing_cycle(): void
    {
        Mail::fake();
        $this->configureGracePeriod(7);

        $seller = User::factory()->seller()->create();
        $renewsAt = now()->subDays(3);
        $sub = $this->createActiveSubscription($seller, $renewsAt);

        // Simulate that the reminder was already sent after the renews_at date
        $sub->payment_request_sent_at = now()->subDay(); // after renews_at
        $sub->save();

        $this->artisan('billing:send-payment-reminders')->assertExitCode(0)->execute();

        Mail::assertNothingSent();
    }

    public function test_overdue_notification_not_sent_twice_within_same_billing_cycle(): void
    {
        Mail::fake();
        $this->configureGracePeriod(7);

        $seller = User::factory()->seller()->create();
        $renewsAt = now()->subDays(9);
        $sub = $this->createActiveSubscription($seller, $renewsAt);

        // Simulate that the overdue notification was already sent after renews_at
        $sub->overdue_notification_sent_at = now()->subDay(); // after renews_at
        $sub->save();

        $this->artisan('billing:send-payment-reminders')->assertExitCode(0)->execute();

        Mail::assertNothingSent();
    }

    public function test_reminder_command_skips_subscriptions_not_yet_due(): void
    {
        Mail::fake();
        $this->configureGracePeriod(7);

        $seller = User::factory()->seller()->create();
        // renews_at is in the future — not yet due
        $this->createActiveSubscription($seller, now()->addDays(5));

        $this->artisan('billing:send-payment-reminders')->assertExitCode(0)->execute();

        Mail::assertNothingSent();
    }

    // ────────────────────────────────────────────────────────────────
    // Webhook → email: publish fee outcomes
    // ────────────────────────────────────────────────────────────────

    public function test_publish_fee_payment_confirmed_via_webhook_sends_confirmation_email(): void
    {
        Mail::fake();
        $this->configureWebhookSecret();

        $seller = User::factory()->seller()->create();

        // Active subscription so the property can exist
        $this->createActiveSubscription($seller, now()->addMonth());

        $property = Property::query()->create([
            'user_id'        => $seller->id,
            'title'          => 'Fee Test Property',
            'description'    => 'E2E publish fee email test',
            'price_ugx'      => 1200000,
            'listing_type'   => 'rent',
            'property_type'  => 'apartment',
            'district'       => 'Kampala',
            'city'           => 'Kampala',
            'address'        => 'Fee Street',
            'status'         => 'draft',
        ]);

        $feeRef = 'pub_fee_ref_001';
        $feeTrk = 'FEE_TRK_001';

        $fee = SellerPublishFee::query()->create([
            'user_id'                  => $seller->id,
            'property_id'              => $property->id,
            'provider'                 => 'pesapal',
            'amount_ugx'               => 7500,
            'currency'                 => 'UGX',
            'payment_method'           => 'mobile_money',
            'status'                   => 'failed',
            'payment_status'           => 'pending',
            'reference'                => $feeRef,
            'provider_transaction_id'  => $feeTrk,
            'checkout_session_id'      => $feeTrk,
        ]);

        // The webhook lookup falls through to the fee table (no subscription matches this reference)
        $this->sendWebhook([
            'event_id'   => 'evt_fee_paid_001',
            'event_type' => 'payment.completed',
            'data'       => [
                'provider_reference' => $feeRef,
                'transaction_id'     => $feeTrk,
                'channel'            => 'MOBILE',
            ],
        ])->assertOk();

        Mail::assertSent(SellerBillingPaymentMail::class, function (SellerBillingPaymentMail $mail) use ($seller) {
            return $mail->subjectLine === 'Property publish fee payment confirmed'
                && $mail->hasTo($seller->email);
        });

        $this->assertDatabaseHas('seller_publish_fees', [
            'id'             => $fee->id,
            'status'         => 'charged',
            'payment_status' => 'paid',
        ]);

        // Property should be auto-published on fee paid
        $this->assertDatabaseHas('properties', [
            'id'     => $property->id,
            'status' => 'published',
        ]);
    }

    public function test_publish_fee_payment_failed_via_webhook_sends_failure_email(): void
    {
        Mail::fake();
        $this->configureWebhookSecret();

        $seller = User::factory()->seller()->create();
        $this->createActiveSubscription($seller, now()->addMonth());

        $property = Property::query()->create([
            'user_id'        => $seller->id,
            'title'          => 'Fee Fail Property',
            'description'    => 'Publish fee failure test',
            'price_ugx'      => 900000,
            'listing_type'   => 'sale',
            'property_type'  => 'house',
            'district'       => 'Wakiso',
            'city'           => 'Entebbe',
            'address'        => 'Fail Road',
            'status'         => 'draft',
        ]);

        $feeRef = 'pub_fee_ref_fail_001';

        SellerPublishFee::query()->create([
            'user_id'        => $seller->id,
            'property_id'    => $property->id,
            'provider'       => 'pesapal',
            'amount_ugx'     => 7500,
            'currency'       => 'UGX',
            'payment_method' => 'mobile_money',
            'status'         => 'failed',
            'payment_status' => 'pending',
            'reference'      => $feeRef,
        ]);

        $this->sendWebhook([
            'event_id'   => 'evt_fee_fail_001',
            'event_type' => 'payment.invalid',
            'data'       => [
                'provider_reference' => $feeRef,
                'channel'            => 'MOBILE',
            ],
        ])->assertOk();

        Mail::assertSent(SellerBillingPaymentMail::class, function (SellerBillingPaymentMail $mail) use ($seller) {
            return $mail->subjectLine === 'Property publish fee payment failed'
                && $mail->hasTo($seller->email);
        });

        $this->assertDatabaseHas('properties', [
            'id'     => $property->id,
            'status' => 'draft', // NOT published on failure
        ]);
    }
}
