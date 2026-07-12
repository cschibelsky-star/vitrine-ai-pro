<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'external_id')) {
                $table->string('external_id', 191)->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('leads', 'pagina_origem')) {
                $table->string('pagina_origem')->nullable()->index()->after('origem_lead');
            }

            if (! Schema::hasColumn('leads', 'campanha')) {
                $table->string('campanha')->nullable()->index()->after('pagina_origem');
            }

            if (! Schema::hasColumn('leads', 'consentimento_lgpd')) {
                $table->boolean('consentimento_lgpd')->default(false)->after('campanha');
            }

            if (! Schema::hasColumn('leads', 'capturado_em')) {
                $table->timestamp('capturado_em')->nullable()->after('consentimento_lgpd');
            }

            if (! Schema::hasColumn('leads', 'metadata')) {
                $table->json('metadata')->nullable()->after('capturado_em');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $columns = [
                'external_id',
                'pagina_origem',
                'campanha',
                'consentimento_lgpd',
                'capturado_em',
                'metadata',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
