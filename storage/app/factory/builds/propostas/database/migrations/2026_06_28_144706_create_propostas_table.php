<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('propostas')) {
            return;
        }

        Schema::create('propostas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('licitacao_id')->constrained('licitacaos')->cascadeOnDelete();
            $table->foreignId('fornecedor_id')->constrained('fornecedors')->cascadeOnDelete();
            $table->decimal('valor', 12, 2)->default(0);
            $table->string('status');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propostas');
    }
};
