<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plan_modules')) {
            Schema::create('plan_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
                $table->enum('tipo_inclusao', ['incluido', 'extra', 'premium', 'bloqueado'])->default('incluido');
                $table->decimal('valor_adicional', 10, 2)->default(0);
                $table->string('limite_uso')->nullable();
                $table->text('observacoes')->nullable();
                $table->enum('status', ['Ativo', 'Inativo'])->default('Ativo');
                $table->timestamps();
                $table->unique(['plan_id', 'module_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_modules');
    }
};
