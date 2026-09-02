<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->string('billing_unit', 40)->default('tokens')->after('tier');
            $table->decimal('unit_cost_brl', 14, 6)->default(0)->after('output_cost_per_million');
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropColumn(['billing_unit', 'unit_cost_brl']);
        });
    }
};
