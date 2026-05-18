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
        Schema::create('seller_publish_fees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_ugx');
            $table->string('currency', 8)->default('UGX');
            $table->enum('status', ['charged', 'waived', 'failed'])->default('charged');
            $table->timestamp('charged_at')->nullable();
            $table->string('reference')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'property_id']);
            $table->index(['status', 'charged_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_publish_fees');
    }
};
