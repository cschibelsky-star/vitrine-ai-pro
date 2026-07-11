<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->uuid('workflow_uuid')->nullable();
            $table->uuid('execution_uuid')->nullable();
            $table->uuid('trace_id')->nullable();
            $table->uuid('correlation_id')->nullable();
            $table->string('event_type', 120);
            $table->string('actor_type', 60)->default('system');
            $table->string('actor_id', 190)->nullable();
            $table->string('source', 120)->default('vitrine-flow');
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('context')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['company_id', 'occurred_at']);
            $table->index(['workflow_uuid', 'execution_uuid']);
            $table->index(['event_type', 'occurred_at']);
            $table->index('correlation_id');
        });

        Schema::create('flow_compliance_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('request_type', 40);
            $table->string('subject_type', 80)->nullable();
            $table->string('subject_reference', 190)->nullable();
            $table->string('legal_basis', 120)->nullable();
            $table->string('status', 40)->default('pending');
            $table->unsignedInteger('retention_days')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('requested_by', 190)->nullable();
            $table->string('processed_by', 190)->nullable();
            $table->text('reason')->nullable();
            $table->json('scope')->nullable();
            $table->json('result')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['request_type', 'status']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_compliance_requests');
        Schema::dropIfExists('flow_audit_logs');
    }
};
