<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->boolean('is_verified')->default(false)->after('is_experimental');
            $table->index(['is_active', 'is_verified'], 'ai_models_active_verified_index');
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropIndex('ai_models_active_verified_index');
            $table->dropColumn('is_verified');
        });
    }
};
