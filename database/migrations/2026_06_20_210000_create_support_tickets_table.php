<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_tickets')) {
            Schema::create('support_tickets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
                $table->foreignId('module_id')->nullable()->constrained('modules')->nullOnDelete();
                $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('priority', ['Baixa', 'Média', 'Alta', 'Urgente'])->default('Média');
                $table->enum('status', ['Aberto', 'Em análise', 'Em execução', 'Aguardando cliente', 'Resolvido', 'Cancelado'])->default('Aberto');
                $table->text('internal_notes')->nullable();
                $table->text('client_response')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
