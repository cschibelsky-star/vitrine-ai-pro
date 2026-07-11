<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_workflows', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('workflow_key', 160);
            $table->string('name', 190);
            $table->string('version', 40)->default('1.0.0');
            $table->string('category', 80)->nullable();
            $table->string('owner', 120)->nullable();
            $table->string('queue', 120)->default('default');
            $table->unsignedInteger('priority')->default(50);
            $table->unsignedInteger('sla_seconds')->nullable();
            $table->unsignedInteger('timeout_seconds')->default(300);
            $table->unsignedInteger('max_retries')->default(3);
            $table->unsignedInteger('retry_backoff_seconds')->default(60);
            $table->decimal('estimated_cost', 14, 6)->default(0);
            $table->decimal('actual_cost', 14, 6)->default(0);
            $table->string('default_provider', 80)->nullable();
            $table->string('n8n_workflow_id', 160)->nullable();
            $table->json('compatibility')->nullable();
            $table->json('feature_flags')->nullable();
            $table->json('metadata')->nullable();
            $table->longText('documentation')->nullable();
            $table->string('status', 40)->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['workflow_key', 'version']);
            $table->index(['category', 'queue']);
            $table->index(['status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_workflows');
    }
};
