<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('plano', 100)->nullable();
            $table->decimal('valor', 10, 2)->default(0);
            $table->date('inicio')->nullable();
            $table->date('vencimento')->nullable();
            $table->enum('status', ['Ativa', 'Trial', 'Homologação', 'Suspensa', 'Cancelada'])->default('Ativa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
