<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'product_id')) {
                    $table->foreignId('product_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('payments', 'plan_id')) {
                    $table->foreignId('plan_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('payments', 'contract_id')) {
                    $table->foreignId('contract_id')->nullable()->after('plan_id')->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('payments', 'tipo_cobranca')) {
                    $table->string('tipo_cobranca')->default('mensalidade')->after('contract_id');
                }
                if (! Schema::hasColumn('payments', 'descricao')) {
                    $table->string('descricao')->nullable()->after('tipo_cobranca');
                }
                if (! Schema::hasColumn('payments', 'competencia')) {
                    $table->date('competencia')->nullable()->after('valor');
                }
                if (! Schema::hasColumn('payments', 'data_pagamento')) {
                    $table->date('data_pagamento')->nullable()->after('vencimento');
                }
                if (! Schema::hasColumn('payments', 'forma_pagamento')) {
                    $table->string('forma_pagamento')->nullable()->after('data_pagamento');
                }
                if (! Schema::hasColumn('payments', 'link_pagamento')) {
                    $table->string('link_pagamento')->nullable()->after('status');
                }
                if (! Schema::hasColumn('payments', 'referencia_externa')) {
                    $table->string('referencia_externa')->nullable()->after('link_pagamento');
                }
                if (! Schema::hasColumn('payments', 'asaas_id')) {
                    $table->string('asaas_id')->nullable()->after('referencia_externa');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                foreach (['asaas_id','referencia_externa','link_pagamento','forma_pagamento','data_pagamento','competencia','descricao','tipo_cobranca','contract_id','plan_id','product_id'] as $column) {
                    if (Schema::hasColumn('payments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
