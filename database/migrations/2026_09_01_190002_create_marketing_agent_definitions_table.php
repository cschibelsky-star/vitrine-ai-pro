<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_agent_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->string('agent_key', 120);
            $table->string('name', 180);
            $table->string('type', 40);
            $table->string('version', 30)->default('1.0.0');
            $table->string('input_schema', 255)->nullable();
            $table->string('output_schema', 255)->nullable();
            $table->string('prompt_version', 30)->default('1.0.0');
            $table->json('depends_on')->nullable();
            $table->json('permissions')->nullable();
            $table->unsignedSmallInteger('max_attempts')->default(2);
            $table->unsignedInteger('timeout_seconds')->default(300);
            $table->boolean('enabled')->default(true);
            $table->boolean('may_block_pipeline')->default(false);
            $table->timestamps();
            $table->unique(['company_id', 'agent_key', 'version'], 'marketing_agent_definitions_unique');
            $table->index(['company_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_agent_definitions');
    }
};
