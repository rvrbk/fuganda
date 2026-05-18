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
        Schema::table('seller_publish_fees', function (Blueprint $table): void {
            if (! Schema::hasColumn('seller_publish_fees', 'payment_status')) {
                $table->string('payment_status', 32)->nullable()->after('status');
                $table->index('payment_status');
            }

            if (! Schema::hasColumn('seller_publish_fees', 'payment_method')) {
                $table->string('payment_method', 32)->nullable()->after('currency');
            }

            if (! Schema::hasColumn('seller_publish_fees', 'checkout_session_id')) {
                $table->string('checkout_session_id', 255)->nullable()->after('payment_method');
                $table->index('checkout_session_id');
            }

            if (! Schema::hasColumn('seller_publish_fees', 'provider')) {
                $table->string('provider', 64)->nullable()->after('property_id');
                $table->index('provider');
            }

            if (! Schema::hasColumn('seller_publish_fees', 'provider_transaction_id')) {
                $table->string('provider_transaction_id', 255)->nullable()->after('checkout_session_id');
            }

            if (! Schema::hasColumn('seller_publish_fees', 'provider_last_event_id')) {
                $table->string('provider_last_event_id', 255)->nullable()->after('provider_transaction_id');
            }

            if (! Schema::hasColumn('seller_publish_fees', 'callback_received_at')) {
                $table->timestamp('callback_received_at')->nullable()->after('provider_last_event_id');
            }

            if (! Schema::hasColumn('seller_publish_fees', 'payment_request_sent_at')) {
                $table->timestamp('payment_request_sent_at')->nullable()->after('callback_received_at');
            }
        });

        Schema::table('seller_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('seller_subscriptions', 'payment_request_sent_at')) {
                $table->timestamp('payment_request_sent_at')->nullable()->after('callback_received_at');
            }

            if (! Schema::hasColumn('seller_subscriptions', 'overdue_notification_sent_at')) {
                $table->timestamp('overdue_notification_sent_at')->nullable()->after('payment_request_sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_publish_fees', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('seller_publish_fees', 'payment_request_sent_at')) {
                $dropColumns[] = 'payment_request_sent_at';
            }
            if (Schema::hasColumn('seller_publish_fees', 'callback_received_at')) {
                $dropColumns[] = 'callback_received_at';
            }
            if (Schema::hasColumn('seller_publish_fees', 'provider_last_event_id')) {
                $dropColumns[] = 'provider_last_event_id';
            }
            if (Schema::hasColumn('seller_publish_fees', 'provider_transaction_id')) {
                $dropColumns[] = 'provider_transaction_id';
            }
            if (Schema::hasColumn('seller_publish_fees', 'provider')) {
                $dropColumns[] = 'provider';
            }
            if (Schema::hasColumn('seller_publish_fees', 'checkout_session_id')) {
                $dropColumns[] = 'checkout_session_id';
            }
            if (Schema::hasColumn('seller_publish_fees', 'payment_method')) {
                $dropColumns[] = 'payment_method';
            }
            if (Schema::hasColumn('seller_publish_fees', 'payment_status')) {
                $dropColumns[] = 'payment_status';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('seller_subscriptions', function (Blueprint $table): void {
            $dropColumns = [];

            if (Schema::hasColumn('seller_subscriptions', 'overdue_notification_sent_at')) {
                $dropColumns[] = 'overdue_notification_sent_at';
            }
            if (Schema::hasColumn('seller_subscriptions', 'payment_request_sent_at')) {
                $dropColumns[] = 'payment_request_sent_at';
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
