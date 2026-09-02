<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('via_signals', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 120);
            $table->string('domain', 80);
            $table->string('project_id', 160)->nullable();
            $table->string('source', 120);
            $table->string('severity', 20)->default('info')->index();
            $table->decimal('confidence', 5, 4)->default(1);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->json('evidence')->nullable();
            $table->string('fingerprint', 64)->unique();
            $table->unsignedInteger('occurrences')->default(1);
            $table->string('status', 24)->default('open')->index();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['domain', 'project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('via_signals');
    }
};
