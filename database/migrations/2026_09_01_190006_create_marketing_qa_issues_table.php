<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_qa_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->foreignId('qa_task_id')->constrained('marketing_campaign_tasks')->cascadeOnDelete();
            $table->foreignId('target_task_id')->nullable()->constrained('marketing_campaign_tasks')->nullOnDelete();
            $table->foreignId('target_artifact_id')->nullable()->constrained('marketing_artifact_versions')->nullOnDelete();
            $table->foreignId('resolved_by_artifact_id')->nullable()->constrained('marketing_artifact_versions')->nullOnDelete();
            $table->string('severity', 30);
            $table->string('rule_key', 120);
            $table->text('message');
            $table->text('suggested_action')->nullable();
            $table->string('status', 30)->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status', 'severity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_qa_issues');
    }
};
