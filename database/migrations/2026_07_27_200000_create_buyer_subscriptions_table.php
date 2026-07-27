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
        Schema::create('buyer_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('plan_code', 100);
            $table->unsignedBigInteger('amount_ugx');
            $table->string('currency', 8)->default('UGX');
            $table->string('provider', 50);
            $table->string('reference', 100);
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference_masked', 100)->nullable();
            $table->string('billing_email', 255)->nullable();
            $table->string('provider_transaction_id', 100)->nullable();
            $table->string('provider_reference', 100)->nullable();
            $table->string('provider_last_event_id', 100)->nullable();
            $table->string('checkout_session_id', 100)->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'expired', 'refunded'])->nullable();
            $table->enum('status', ['active', 'inactive', 'past_due'])->default('inactive');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->timestamp('payment_request_sent_at')->nullable();
            $table->timestamp('overdue_notification_sent_at')->nullable();
            $table->timestamp('charged_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'renews_at']);
            $table->index(['user_id']);
            $table->index(['provider_transaction_id']);
            $table->index(['reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_subscriptions');
    }
};
