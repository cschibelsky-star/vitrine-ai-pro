<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_telemetry', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->uuid('workflow_uuid')->nullable();
            $table->string('execution_id', 190)->nullable();
            $table->string('trace_id', 190)->nullable();
            $table->string('correlation_id', 190)->nullable();
            $table->string('provider', 100)->nullable();
            $table->string('queue', 100)->nullable();
            $table->string('status', 60);
            $table->string('step', 120)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->decimal('estimated_cost', 14, 6)->default(0);
            $table->decimal('actual_cost', 14, 6)->default(0);
            $table->unsignedBigInteger('tokens')->default(0);
            $table->decimal('minutes', 12, 4)->default(0);
            $table->unsignedBigInteger('storage_bytes')->default(0);
            $table->json('metrics')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'occurred_at']);
            $table->index(['workflow_uuid', 'execution_id']);
            $table->index(['trace_id', 'correlation_id']);
        });

        Schema::create('flow_dlq_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('entry_uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->uuid('workflow_uuid')->nullable();
            $table->string('execution_id', 190)->nullable();
            $table->string('trace_id', 190)->nullable();
            $table->string('correlation_id', 190)->nullable();
            $table->string('provider', 100)->nullable();
            $table->string('queue', 100)->nullable();
            $table->unsignedInteger('attempts')->default(1);
            $table->string('failure_code', 100)->nullable();
            $table->text('failure_reason');
            $table->text('exception_class')->nullable();
            $table->longText('exception_message')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->json('payload')->nullable();
            $table->json('metadata')->nullable();
            $table->string('status', 40)->default('pending');
            $table->boolean('reprocessable')->default(true);
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('reprocessed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'failed_at']);
            $table->index(['workflow_uuid', 'execution_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_dlq_entries');
        Schema::dropIfExists('flow_telemetry');
    }
};
