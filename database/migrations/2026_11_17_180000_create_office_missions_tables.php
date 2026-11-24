<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('office_missions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('domain', 3);
            $table->string('title', 180);
            $table->text('description');
            $table->string('status', 40)->default('planned');
            $table->string('priority', 12)->default('normal');
            $table->uuid('idempotency_key')->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['created_by', 'domain', 'created_at']);
            $table->index(['domain', 'status']);
        });

        Schema::create('office_mission_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('mission_id')->constrained('office_missions')->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40);
            $table->uuid('request_id')->unique();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->index(['mission_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_mission_events');
        Schema::dropIfExists('office_missions');
    }
};
