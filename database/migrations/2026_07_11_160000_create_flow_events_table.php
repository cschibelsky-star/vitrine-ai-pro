<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type', 120)->index();
            $table->string('source', 80)->default('vitrine-ia-flow')->index();
            $table->string('workflow', 160)->nullable()->index();
            $table->string('execution_id', 160)->nullable()->index();
            $table->string('status', 60)->default('received')->index();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->string('step', 160)->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_events');
    }
};
