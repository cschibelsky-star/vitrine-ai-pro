<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$result = [
    'products' => DB::table('products')->orderBy('nome')->pluck('nome')->all(),
    'plans' => DB::table('plans')
        ->leftJoin('products', 'products.id', '=', 'plans.product_id')
        ->orderBy('products.nome')
        ->orderBy('plans.nome')
        ->get([
            'products.nome as product',
            'plans.nome as plan',
            'plans.valor_mensal',
            'plans.valor_implantacao',
            'plans.ciclo_cobranca',
            'plans.status',
        ])->all(),
    'companies' => DB::table('companies')->orderBy('nome')->get([
        'nome',
        'produto_principal',
        'status',
        'status_implantacao',
        'dominio_principal',
        'tipo_instancia',
    ])->all(),
    'licenses' => DB::table('licenses')
        ->leftJoin('companies', 'companies.id', '=', 'licenses.company_id')
        ->leftJoin('products', 'products.id', '=', 'licenses.product_id')
        ->leftJoin('plans', 'plans.id', '=', 'licenses.plan_id')
        ->orderBy('companies.nome')
        ->get([
            'companies.nome as company',
            'products.nome as product',
            'licenses.plano',
            'plans.nome as linked_plan',
            'licenses.valor',
            'licenses.status',
            'licenses.inicio',
            'licenses.vencimento',
        ])->all(),
    'counts' => [
        'products' => DB::table('products')->count(),
        'plans' => DB::table('plans')->count(),
        'companies' => DB::table('companies')->count(),
        'licenses' => DB::table('licenses')->count(),
        'modules' => Schema::hasTable('modules') ? DB::table('modules')->count() : 0,
        'plan_modules' => Schema::hasTable('plan_modules') ? DB::table('plan_modules')->count() : 0,
        'company_modules' => Schema::hasTable('company_modules') ? DB::table('company_modules')->count() : 0,
        'settings' => Schema::hasTable('settings') ? DB::table('settings')->count() : 0,
    ],
    'schema' => [
        'companies' => Schema::getColumnListing('companies'),
        'licenses' => Schema::getColumnListing('licenses'),
    ],
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
