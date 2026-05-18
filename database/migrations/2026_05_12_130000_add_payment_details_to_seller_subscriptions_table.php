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
        Schema::table('seller_subscriptions', function (Blueprint $table) {
            $table->string('payment_method', 32)->nullable()->after('currency');
            $table->string('payment_reference_masked', 120)->nullable()->after('payment_method');
            $table->string('billing_email')->nullable()->after('payment_reference_masked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference_masked', 'billing_email']);
        });
    }
};
