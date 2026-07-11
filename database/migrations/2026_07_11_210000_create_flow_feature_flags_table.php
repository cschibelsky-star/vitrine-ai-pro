<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('key', 120);
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->uuid('workflow_uuid')->nullable();
            $table->boolean('enabled')->default(false);
            $table->boolean('beta')->default(false);
            $table->unsignedTinyInteger('rollout_percentage')->default(100);
            $table->unsignedInteger('priority')->default(100);
            $table->json('config')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamps();

            $table->index(['key', 'company_id', 'plan_id']);
            $table->index(['workflow_uuid', 'status']);
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_feature_flags');
    }
};
