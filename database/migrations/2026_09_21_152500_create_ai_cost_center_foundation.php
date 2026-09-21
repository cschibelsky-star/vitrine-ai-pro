<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_consumers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('name', 160);
            $table->string('type', 60)->default('product');
            $table->string('status', 30)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('ai_consumptions', function (Blueprint $table) {
            // Keep this column as a plain indexed bigint. Adding a foreign key in
            // SQLite forces a table rebuild and can drop existing legacy columns
            // during tests/pretend runs. Referential integrity is handled by the
            // application while preserving portability across SQLite and MariaDB.
            $table->unsignedBigInteger('ai_consumer_id')->nullable()->after('id')->index();
            $table->string('gateway', 80)->nullable()->after('ai_provider_id')->index();
            $table->string('model_name', 160)->nullable()->after('gateway')->index();
            $table->string('capability', 80)->nullable()->after('model_name')->index();
            $table->string('request_id', 191)->nullable()->after('capability')->index();
            $table->string('unit_type', 40)->nullable()->after('resource_type');
            $table->decimal('input_units', 18, 4)->default(0)->after('quantity');
            $table->decimal('output_units', 18, 4)->default(0)->after('input_units');
            $table->decimal('actual_cost', 14, 6)->nullable()->after('estimated_cost');
            $table->decimal('original_cost', 14, 6)->nullable()->after('actual_cost');
            $table->char('original_currency', 3)->default('BRL')->after('original_cost');
            $table->decimal('fx_rate', 14, 6)->nullable()->after('original_currency');
            $table->decimal('cost_brl', 14, 6)->nullable()->after('fx_rate')->index();
            $table->string('status', 40)->default('completed')->after('cost_brl')->index();
            $table->unsignedInteger('latency_ms')->nullable()->after('status');
            $table->char('billing_period', 7)->nullable()->after('latency_ms')->index();
            $table->timestamp('occurred_at')->nullable()->after('consumption_date')->index();
            $table->json('metadata')->nullable()->after('notes');

            $table->index(['ai_consumer_id', 'billing_period']);
            $table->index(['gateway', 'billing_period']);
            $table->index(['model_name', 'billing_period']);
        });
    }

    public function down(): void
    {
        Schema::table('ai_consumptions', function (Blueprint $table) {
            $table->dropIndex(['ai_consumer_id', 'billing_period']);
            $table->dropIndex(['gateway', 'billing_period']);
            $table->dropIndex(['model_name', 'billing_period']);
            $table->dropColumn([
                'ai_consumer_id',
                'gateway',
                'model_name',
                'capability',
                'request_id',
                'unit_type',
                'input_units',
                'output_units',
                'actual_cost',
                'original_cost',
                'original_currency',
                'fx_rate',
                'cost_brl',
                'status',
                'latency_ms',
                'billing_period',
                'occurred_at',
                'metadata',
            ]);
        });

        Schema::dropIfExists('ai_consumers');
    }
};
