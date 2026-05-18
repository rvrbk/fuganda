<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasCheckoutSessionId = Schema::hasColumn('seller_subscriptions', 'checkout_session_id');
        $hasPaymentStatus = Schema::hasColumn('seller_subscriptions', 'payment_status');
        $hasActivatedAt = Schema::hasColumn('seller_subscriptions', 'activated_at');

        Schema::table('seller_subscriptions', function (Blueprint $table) use ($hasCheckoutSessionId, $hasPaymentStatus, $hasActivatedAt) {
            if (! $hasCheckoutSessionId) {
                $table->string('checkout_session_id', 255)->nullable()->after('billing_email');
                $table->index('checkout_session_id');
            }

            if (! $hasPaymentStatus) {
                $table->string('payment_status', 32)->default('pending')->after('checkout_session_id');
            }

            if (! $hasActivatedAt) {
                $table->timestamp('activated_at')->nullable()->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $columnsToDrop = [];

        if (Schema::hasColumn('seller_subscriptions', 'checkout_session_id')) {
            $columnsToDrop[] = 'checkout_session_id';
        }

        if (Schema::hasColumn('seller_subscriptions', 'payment_status')) {
            $columnsToDrop[] = 'payment_status';
        }

        if (Schema::hasColumn('seller_subscriptions', 'activated_at')) {
            $columnsToDrop[] = 'activated_at';
        }

        if ($columnsToDrop !== []) {
            Schema::table('seller_subscriptions', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
