<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_modules')) {
            Schema::create('company_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
                $table->enum('tipo_contratacao', ['plano', 'extra', 'premium', 'cortesia', 'implantacao', 'bloqueado'])->default('plano');
                $table->decimal('valor_mensal_adicional', 10, 2)->default(0);
                $table->date('data_inicio')->nullable();
                $table->date('data_fim')->nullable();
                $table->enum('status', ['Ativo', 'Implantação', 'Bloqueado', 'Suspenso', 'Futuro'])->default('Ativo');
                $table->text('observacoes')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'module_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_modules');
    }
};
