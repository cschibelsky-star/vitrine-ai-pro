<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->string('nome');
                $table->string('codigo')->unique();
                $table->text('descricao')->nullable();
                $table->string('categoria')->nullable();
                $table->enum('tipo', ['incluido', 'extra', 'premium', 'interno', 'futuro'])->default('incluido');
                $table->decimal('valor_adicional', 10, 2)->default(0);
                $table->enum('status', ['Ativo', 'Inativo', 'Implantação', 'Bloqueado', 'Futuro'])->default('Ativo');
                $table->integer('ordem')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
