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
        Schema::table('seller_subscriptions', function (Blueprint $table): void {
            $table->string('provider', 64)->nullable()->after('user_id');
            $table->string('provider_transaction_id', 255)->nullable()->after('billing_email');
            $table->string('provider_reference', 255)->nullable()->after('provider_transaction_id');
            $table->string('provider_last_event_id', 255)->nullable()->after('provider_reference');
            $table->timestamp('callback_received_at')->nullable()->after('provider_last_event_id');

            $table->index('provider');
            $table->index('provider_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['provider']);
            $table->dropIndex(['provider_reference']);
            $table->dropColumn([
                'provider',
                'provider_transaction_id',
                'provider_reference',
                'provider_last_event_id',
                'callback_received_at',
            ]);
        });
    }
};
