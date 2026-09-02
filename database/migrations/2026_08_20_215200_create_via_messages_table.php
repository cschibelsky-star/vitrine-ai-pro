<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('via_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('via_conversation_id')->constrained('via_conversations')->cascadeOnDelete();
            $table->string('role', 20);
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['via_conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('via_messages');
    }
};
