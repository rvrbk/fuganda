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
        Schema::dropIfExists('seller_publish_fees');
        Schema::dropIfExists('seller_subscriptions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to recreate - these tables are being permanently removed
        // as part of removing the seller subscription feature
    }
};