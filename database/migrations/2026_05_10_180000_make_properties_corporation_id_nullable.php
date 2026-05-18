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
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropForeign(['corporation_id']);
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->unsignedBigInteger('corporation_id')->nullable()->change();
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->foreign('corporation_id')->references('id')->on('corporations')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropForeign(['corporation_id']);
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->unsignedBigInteger('corporation_id')->nullable(false)->change();
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->foreign('corporation_id')->references('id')->on('corporations')->cascadeOnDelete();
        });
    }
};
