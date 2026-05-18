<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('price_currency', 3)->default('UGX')->after('price_ugx');
            $table->index('price_currency');
        });

        DB::table('properties')
            ->whereNull('price_currency')
            ->update(['price_currency' => 'UGX']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['price_currency']);
            $table->dropColumn('price_currency');
        });
    }
};