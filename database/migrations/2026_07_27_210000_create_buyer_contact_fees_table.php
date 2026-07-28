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
        Schema::create('buyer_contact_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('provider', 50);
            $table->unsignedBigInteger('amount_ugx');
            $table->string('currency', 8)->default('UGX');
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference_masked', 100)->nullable();
            $table->string('billing_email', 255)->nullable();
            $table->string('checkout_session_id', 100)->nullable();
            $table->string('provider_transaction_id', 100)->nullable();
            $table->string('provider_last_event_id', 100)->nullable();
            $table->string('reference', 100);
            $table->enum('status', ['charged', 'waived', 'failed'])->default('failed');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'expired', 'refunded'])->nullable();
            $table->timestamp('charged_at')->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->timestamp('payment_request_sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'property_id']);
            $table->index(['user_id']);
            $table->index(['property_id']);
            $table->index(['provider_transaction_id']);
            $table->index(['reference']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_contact_fees');
    }
};
