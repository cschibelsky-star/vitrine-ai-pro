<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compatibilidade: a tabela ja e criada pela migration 2026_06_28_002029.
        // Mantemos esta migration como no-op para preservar o historico sem recriar a tabela.
    }

    public function down(): void
    {
        // No-op: a tabela pertence a migration 2026_06_28_002029.
    }
};
