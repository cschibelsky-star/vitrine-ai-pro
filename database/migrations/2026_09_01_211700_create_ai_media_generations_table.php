<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_media_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
            $table->string('capability', 80);
            $table->string('model_name', 160)->nullable();
            $table->string('status', 40)->default('Pendente');
            $table->longText('input');
            $table->longText('output')->nullable();
            $table->string('operation_id', 255)->nullable()->index();
            $table->string('asset_url', 2048)->nullable();
            $table->string('asset_path', 2048)->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['capability', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_media_generations');
    }
};
