<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('slug', 190);
            $table->string('provider_model_id', 190);
            $table->string('modality', 40)->default('text');
            $table->string('tier', 30)->default('balanced');
            $table->unsignedInteger('context_window')->nullable();
            $table->decimal('input_cost_per_million', 14, 6)->default(0);
            $table->decimal('output_cost_per_million', 14, 6)->default(0);
            $table->json('capabilities')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_experimental')->default(true);
            $table->timestamps();

            $table->unique(['ai_provider_id', 'provider_model_id'], 'ai_models_provider_model_unique');
            $table->index(['modality', 'tier', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
