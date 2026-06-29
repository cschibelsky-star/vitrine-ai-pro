<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'dominio_landing')) {
                $table->string('dominio_landing')->nullable()->after('dominio_principal');
            }
            if (! Schema::hasColumn('companies', 'dominio_demo')) {
                $table->string('dominio_demo')->nullable()->after('dominio_landing');
            }
            if (! Schema::hasColumn('companies', 'tipo_instancia')) {
                $table->enum('tipo_instancia', ['piloto', 'cliente', 'demo', 'white_label', 'interno'])->nullable()->after('ambiente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach (['dominio_landing', 'dominio_demo', 'tipo_instancia'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
