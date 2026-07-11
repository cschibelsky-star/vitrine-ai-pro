<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_executions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('flow_workflow_id')->nullable()->constrained('flow_workflows')->nullOnDelete();
            $table->uuid('workflow_uuid');
            $table->uuid('trace_id')->index();
            $table->uuid('correlation_id')->nullable()->index();
            $table->string('status', 50)->default('queued')->index();
            $table->string('queue', 100)->default('default');
            $table->unsignedSmallInteger('priority')->default(100);
            $table->string('provider', 100)->nullable();
            $table->string('lock_owner', 190)->nullable();
            $table->uuid('usage_reservation_uuid')->nullable();
            $table->json('input')->nullable();
            $table->json('context')->nullable();
            $table->json('output')->nullable();
            $table->text('failure_reason')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['workflow_uuid', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_executions');
    }
};
