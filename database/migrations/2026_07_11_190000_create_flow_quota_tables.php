<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('metric', 100);
            $table->string('period', 30)->default('monthly');
            $table->decimal('limit_value', 18, 6);
            $table->boolean('active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'metric', 'period']);
        });

        Schema::create('flow_usage_reservations', function (Blueprint $table) {
            $table->id();
            $table->uuid('reservation_uuid')->unique();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->uuid('workflow_uuid')->nullable()->index();
            $table->string('execution_id', 255)->nullable()->index();
            $table->string('metric', 100);
            $table->decimal('quantity', 18, 6);
            $table->decimal('estimated_cost', 18, 6)->default(0);
            $table->decimal('actual_cost', 18, 6)->default(0);
            $table->string('provider', 100)->nullable();
            $table->string('status', 30)->default('reserved');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('committed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'metric', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_usage_reservations');
        Schema::dropIfExists('flow_quotas');
    }
};
