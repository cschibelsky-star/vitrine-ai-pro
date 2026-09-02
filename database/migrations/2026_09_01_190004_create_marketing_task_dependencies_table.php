<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('campaign_task_id')->constrained('marketing_campaign_tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')->constrained('marketing_campaign_tasks')->cascadeOnDelete();
            $table->string('dependency_type', 30)->default('required');
            $table->timestamps();
            $table->unique(['campaign_task_id', 'depends_on_task_id'], 'marketing_task_dependencies_unique');
            $table->index(['company_id', 'campaign_task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_task_dependencies');
    }
};
