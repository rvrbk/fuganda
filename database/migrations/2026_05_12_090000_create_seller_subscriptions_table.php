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
        Schema::create('seller_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('plan_code', 100);
            $table->unsignedBigInteger('amount_ugx');
            $table->string('currency', 8)->default('UGX');
            $table->enum('status', ['active', 'inactive', 'past_due'])->default('inactive');
            $table->timestamp('started_at');
            $table->timestamp('renews_at');
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'renews_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_subscriptions');
    }
};
