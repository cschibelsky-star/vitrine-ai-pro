<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_campaign_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('marketing_campaigns')->cascadeOnDelete();
            $table->foreignId('agent_definition_id')->constrained('marketing_agent_definitions')->restrictOnDelete();
            $table->string('task_key', 160);
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->unsignedSmallInteger('priority')->default(5);
            $table->text('blocked_reason')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['campaign_id', 'task_key']);
            $table->index(['company_id', 'status', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_campaign_tasks');
    }
};
