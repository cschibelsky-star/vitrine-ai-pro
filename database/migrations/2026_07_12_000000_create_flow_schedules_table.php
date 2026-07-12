<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('flow_workflow_id')->constrained('flow_workflows')->cascadeOnDelete();
            $table->uuid('workflow_uuid')->index();
            $table->string('name', 190);
            $table->string('timezone', 80)->default('America/Sao_Paulo');
            $table->string('recurrence_type', 30)->default('once');
            $table->string('rrule', 500)->nullable();
            $table->json('calendar')->nullable();
            $table->json('execution_window')->nullable();
            $table->json('holidays')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('priority')->default(100);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('next_run_at')->nullable()->index();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('locked_until')->nullable()->index();
            $table->string('status', 30)->default('active')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_schedules');
    }
};
