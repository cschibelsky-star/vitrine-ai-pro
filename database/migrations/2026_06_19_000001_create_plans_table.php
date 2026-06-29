<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->string('nome');
                $table->decimal('valor_mensal', 10, 2)->default(0.00);
                $table->decimal('valor_implantacao', 10, 2)->default(0.00);
                $table->enum('ciclo_cobranca', ['mensal','anual','implantacao','trial','cortesia'])->default('mensal');
                $table->text('descricao')->nullable();
                $table->text('recursos')->nullable();
                $table->enum('status', ['Ativo', 'Inativo'])->default('Ativo');
                $table->timestamps();
            });
        }
    }
    public function down(): void { Schema::dropIfExists('plans'); }
};
