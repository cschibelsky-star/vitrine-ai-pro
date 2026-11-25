<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factory_agent_dna_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('agent_id');
            $table->unsignedInteger('version');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('domain', 3);
            $table->unsignedBigInteger('company_id')->nullable();
            $table->json('dna');
            $table->timestamp('created_at');
            $table->unique(['agent_id', 'version']);
            $table->index(['created_by', 'domain', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factory_agent_dna_versions');
    }
};
