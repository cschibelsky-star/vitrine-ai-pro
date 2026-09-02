<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_consumptions', function (Blueprint $table) {
            $table->string('model_name', 190)->nullable()->after('ai_provider_id');
            $table->unsignedBigInteger('input_tokens')->default(0)->after('model_name');
            $table->unsignedBigInteger('output_tokens')->default(0)->after('input_tokens');
            $table->unsignedBigInteger('total_tokens')->default(0)->after('output_tokens');
            $table->decimal('provider_cost_brl', 14, 6)->default(0)->after('estimated_cost');
            $table->decimal('billable_cost_brl', 14, 6)->default(0)->after('provider_cost_brl');
            $table->decimal('ai_credits', 14, 4)->default(0)->after('billable_cost_brl');
            $table->unsignedInteger('duration_ms')->default(0)->after('ai_credits');
            $table->string('status', 30)->nullable()->after('duration_ms');
            $table->string('request_id', 100)->nullable()->after('status');
            $table->json('metadata')->nullable()->after('request_id');

            $table->index(['ai_provider_id', 'model_name']);
            $table->index(['status', 'consumption_date']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_consumptions', function (Blueprint $table) {
            $table->dropIndex(['ai_provider_id', 'model_name']);
            $table->dropIndex(['status', 'consumption_date']);
            $table->dropColumn([
                'model_name',
                'input_tokens',
                'output_tokens',
                'total_tokens',
                'provider_cost_brl',
                'billable_cost_brl',
                'ai_credits',
                'duration_ms',
                'status',
                'request_id',
                'metadata',
            ]);
        });
    }
};
