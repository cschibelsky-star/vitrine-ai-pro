<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts')) {
            Schema::create('contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
                $table->string('numero')->nullable()->index();
                $table->string('titulo')->nullable();
                $table->string('tipo_contrato')->default('mensalidade');
                $table->decimal('valor_implantacao', 12, 2)->default(0);
                $table->decimal('valor_mensal', 12, 2)->default(0);
                $table->decimal('valor_modulos_extras', 12, 2)->default(0);
                $table->decimal('valor_total_mensal', 12, 2)->default(0);
                $table->date('data_inicio')->nullable();
                $table->date('data_fim')->nullable();
                $table->unsignedTinyInteger('dia_vencimento')->nullable();
                $table->string('status')->default('rascunho');
                $table->string('link_proposta')->nullable();
                $table->string('link_contrato')->nullable();
                $table->text('observacoes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
