<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migration duplicada historicamente. Mantida por compatibilidade com
        // ambientes onde seu nome já possa constar na tabela migrations.
        // A migration canônica anterior é 2026_06_28_002029_create_fornecedores_table.
        if (Schema::hasTable('fornecedores')) {
            return;
        }

        Schema::create('fornecedores', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('documento')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->string('cidade')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        // Não remove a tabela: ela pertence à migration canônica anterior.
    }
};
