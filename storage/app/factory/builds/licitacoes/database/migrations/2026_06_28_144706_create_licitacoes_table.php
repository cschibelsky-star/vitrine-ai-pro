<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('licitacoes')) {
            return;
        }

        Schema::create('licitacoes', function (Blueprint $table): void {
            $table->id();
            $table->string('numero');
            $table->text('objeto')->nullable();
            $table->string('modalidade')->nullable();
            $table->date('data_abertura')->nullable();
            $table->decimal('valor_estimado', 12, 2)->default(0);
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licitacoes');
    }
};
