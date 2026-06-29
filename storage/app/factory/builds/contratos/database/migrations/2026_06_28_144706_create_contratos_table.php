<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contratos')) {
            return;
        }

        Schema::create('contratos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('fornecedor_id')->constrained('fornecedors')->cascadeOnDelete();
            $table->foreignId('licitacao_id')->constrained('licitacaos')->cascadeOnDelete();
            $table->string('numero');
            $table->text('objeto')->nullable();
            $table->decimal('valor', 12, 2)->default(0);
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
