<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('oauth_provider')->nullable()->after('remember_token');
            $table->string('oauth_provider_id')->nullable()->after('oauth_provider');
            $table->index(['oauth_provider', 'oauth_provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_oauth_provider_oauth_provider_id_index');
            $table->dropColumn(['oauth_provider', 'oauth_provider_id']);
        });
    }
};
