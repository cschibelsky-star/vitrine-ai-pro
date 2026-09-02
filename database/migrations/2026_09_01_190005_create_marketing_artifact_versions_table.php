<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_artifact_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('marketing_campaign_tasks')->nullOnDelete();
            $table->foreignId('supersedes_id')->nullable()->constrained('marketing_artifact_versions')->nullOnDelete();
            $table->string('artifact_type', 80);
            $table->string('artifact_key', 160);
            $table->unsignedInteger('version')->default(1);
            $table->string('schema_version', 30)->default('1.0.0');
            $table->string('status', 30)->default('draft');
            $table->json('content');
            $table->string('checksum', 64);
            $table->string('created_by_type', 30)->default('agent');
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'artifact_key', 'version'], 'marketing_artifact_versions_unique');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_artifact_versions');
    }
};
