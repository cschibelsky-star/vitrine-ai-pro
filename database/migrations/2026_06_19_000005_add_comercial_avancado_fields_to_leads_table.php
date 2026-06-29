<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'origem_lead')) {
                $table->string('origem_lead')->nullable()->after('produto_interesse');
            }
            if (! Schema::hasColumn('leads', 'plano_sugerido')) {
                $table->string('plano_sugerido')->nullable()->after('origem_lead');
            }
            if (! Schema::hasColumn('leads', 'valor_estimado')) {
                $table->decimal('valor_estimado', 10, 2)->nullable()->after('plano_sugerido');
            }
            if (! Schema::hasColumn('leads', 'status_negociacao')) {
                $table->string('status_negociacao')->nullable()->after('valor_estimado');
            }
            if (! Schema::hasColumn('leads', 'responsavel_comercial')) {
                $table->string('responsavel_comercial')->nullable()->after('status_negociacao');
            }
            if (! Schema::hasColumn('leads', 'proxima_acao')) {
                $table->string('proxima_acao')->nullable()->after('responsavel_comercial');
            }
            if (! Schema::hasColumn('leads', 'data_proxima_acao')) {
                $table->date('data_proxima_acao')->nullable()->after('proxima_acao');
            }
            if (! Schema::hasColumn('leads', 'link_proposta')) {
                $table->string('link_proposta', 500)->nullable()->after('data_proxima_acao');
            }
            if (! Schema::hasColumn('leads', 'observacoes_internas')) {
                $table->text('observacoes_internas')->nullable()->after('observacoes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $columns = ['origem_lead','plano_sugerido','valor_estimado','status_negociacao','responsavel_comercial','proxima_acao','data_proxima_acao','link_proposta','observacoes_internas'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
