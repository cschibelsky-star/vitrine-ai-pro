<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\License;
use App\Models\Plan;
use App\Models\PlanModule;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CoreOperationalCatalogRecoverySeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['nome' => 'TV Digital Enterprise', 'categoria' => 'Mídia', 'descricao' => 'Portal e TV digital empresarial.', 'status' => 'Ativo'],
            ['nome' => 'Portal News AI Pro', 'categoria' => 'Mídia', 'descricao' => 'Portal de notícias com automação por IA.', 'status' => 'Ativo'],
            ['nome' => 'Guia Digital da Cidade®', 'categoria' => 'Turismo', 'descricao' => 'Guia digital municipal replicável para turismo, eventos, roteiros e comércio local.', 'status' => 'Ativo'],
            ['nome' => 'SISMED', 'categoria' => 'Saúde', 'descricao' => 'Saúde Digital Inteligente em desenvolvimento e implantação progressiva.', 'status' => 'Ativo'],
            ['nome' => 'Governo Digital IA', 'categoria' => 'Governo', 'descricao' => 'Solução institucional para prefeituras, câmaras, secretarias e autarquias.', 'status' => 'Ativo'],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['nome' => $product['nome']], $product);
        }

        $plans = [
            'TV Digital Enterprise' => [
                ['nome' => 'Start', 'valor_mensal' => 497.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
                ['nome' => 'Pro', 'valor_mensal' => 997.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
                ['nome' => 'Enterprise', 'valor_mensal' => 1500.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
            ],
            'Portal News AI Pro' => [
                ['nome' => 'Start', 'valor_mensal' => 497.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
                ['nome' => 'Pro', 'valor_mensal' => 997.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
                ['nome' => 'Enterprise', 'valor_mensal' => 1500.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
            ],
            'Guia Digital da Cidade®' => [
                ['nome' => 'Beta', 'valor_mensal' => 0.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'trial', 'status' => 'Ativo'],
                ['nome' => 'Start', 'valor_mensal' => 497.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
                ['nome' => 'Pro', 'valor_mensal' => 997.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
                ['nome' => 'Governo', 'valor_mensal' => 1500.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
            ],
            'SISMED' => [
                ['nome' => 'Trial', 'valor_mensal' => 0.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'trial', 'status' => 'Ativo'],
                ['nome' => 'Implantação', 'valor_mensal' => 0.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'implantacao', 'status' => 'Ativo'],
                ['nome' => 'Enterprise', 'valor_mensal' => 2500.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
            ],
            'Governo Digital IA' => [
                ['nome' => 'Implantação', 'valor_mensal' => 0.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'implantacao', 'status' => 'Ativo'],
                ['nome' => 'Governo', 'valor_mensal' => 2500.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
                ['nome' => 'Enterprise', 'valor_mensal' => 4500.00, 'valor_implantacao' => 0.00, 'ciclo_cobranca' => 'mensal', 'status' => 'Ativo'],
            ],
        ];

        foreach ($plans as $productName => $productPlans) {
            $product = Product::where('nome', $productName)->firstOrFail();
            foreach ($productPlans as $plan) {
                Plan::updateOrCreate(
                    ['product_id' => $product->id, 'nome' => $plan['nome']],
                    $plan,
                );
            }
        }

        $companies = [
            ['nome' => 'TV Sumaré', 'responsavel' => 'Administração', 'cidade' => 'Sumaré', 'estado' => 'SP', 'produto_principal' => 'TV Digital Enterprise', 'status' => 'Ativo'],
            ['nome' => 'Conheça Sumaré', 'responsavel' => 'Administração', 'cidade' => 'Sumaré', 'estado' => 'SP', 'produto_principal' => 'Guia Digital da Cidade®', 'status' => 'Homologação'],
            ['nome' => 'SISMED', 'responsavel' => 'Administração', 'cidade' => 'Sumaré', 'estado' => 'SP', 'produto_principal' => 'SISMED', 'status' => 'Implantação'],
        ];

        foreach ($companies as $company) {
            Company::updateOrCreate(['nome' => $company['nome']], $company);
        }

        $licenses = [
            ['company' => 'TV Sumaré', 'product' => 'TV Digital Enterprise', 'plano' => 'Enterprise', 'valor' => 1500.00, 'status' => 'Ativa', 'months' => 12],
            ['company' => 'Conheça Sumaré', 'product' => 'Guia Digital da Cidade®', 'plano' => 'Beta', 'valor' => 0.00, 'status' => 'Homologação', 'months' => 3],
            ['company' => 'SISMED', 'product' => 'SISMED', 'plano' => 'Implantação', 'valor' => 0.00, 'status' => 'Trial', 'months' => 1],
        ];

        foreach ($licenses as $item) {
            $company = Company::where('nome', $item['company'])->firstOrFail();
            $product = Product::where('nome', $item['product'])->firstOrFail();
            $plan = Plan::where('product_id', $product->id)->where('nome', $item['plano'])->firstOrFail();

            $license = License::firstOrNew([
                'company_id' => $company->id,
                'product_id' => $product->id,
            ]);

            $license->fill([
                'plan_id' => $plan->id,
                'plano' => $item['plano'],
                'valor' => $item['valor'],
                'status' => $item['status'],
            ]);

            if (! $license->exists) {
                $license->inicio = now()->toDateString();
                $license->vencimento = now()->addMonths($item['months'])->toDateString();
            }

            $license->save();
        }

        $this->call([
            ModuleSeeder::class,
            SettingSeeder::class,
        ]);

        $this->normalizeLegacyProducts();
        $this->linkLicensePlans();
        $this->materializeCompanyModules();
    }

    private function normalizeLegacyProducts(): void
    {
        $mapping = [
            'Portal News AI' => 'Portal News AI Pro',
            'Visite Cidade' => 'Guia Digital da Cidade®',
            'Município Digital IA' => 'Governo Digital IA',
        ];

        $productReferenceTables = [
            'plans',
            'licenses',
            'modules',
            'payments',
            'contracts',
            'support_tickets',
            'ai_queues',
            'ai_executions',
            'ai_consumptions',
            'ai_memories',
            'marketing_campaigns',
        ];

        foreach ($mapping as $legacyName => $officialName) {
            $legacy = Product::where('nome', $legacyName)->first();
            $official = Product::where('nome', $officialName)->first();

            if (! $legacy || ! $official || $legacy->id === $official->id) {
                continue;
            }

            foreach ($productReferenceTables as $table) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'product_id')) {
                    continue;
                }

                DB::table($table)
                    ->where('product_id', $legacy->id)
                    ->update(['product_id' => $official->id]);
            }

            if (Schema::hasTable('companies') && Schema::hasColumn('companies', 'produto_principal')) {
                DB::table('companies')
                    ->where('produto_principal', $legacyName)
                    ->update(['produto_principal' => $officialName]);
            }

            $references = 0;
            foreach ($productReferenceTables as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'product_id')) {
                    $references += DB::table($table)->where('product_id', $legacy->id)->count();
                }
            }

            if ($references === 0) {
                $legacy->delete();
            }
        }
    }

    private function linkLicensePlans(): void
    {
        License::query()->each(function (License $license): void {
            if (! $license->product_id || ! $license->plano) {
                return;
            }

            $plan = Plan::where('product_id', $license->product_id)
                ->where('nome', $license->plano)
                ->first();

            if ($plan && $license->plan_id !== $plan->id) {
                $license->plan_id = $plan->id;
                $license->save();
            }
        });
    }

    private function materializeCompanyModules(): void
    {
        if (! Schema::hasTable('company_modules')) {
            return;
        }

        License::query()->whereNotNull('plan_id')->each(function (License $license): void {
            PlanModule::query()
                ->where('plan_id', $license->plan_id)
                ->each(function (PlanModule $planModule) use ($license): void {
                    $status = match (true) {
                        $planModule->status === 'Inativo' => 'Futuro',
                        $planModule->tipo_inclusao === 'incluido' => 'Ativo',
                        default => 'Bloqueado',
                    };

                    $tipo = match ($planModule->tipo_inclusao) {
                        'extra' => 'extra',
                        'premium' => 'premium',
                        'bloqueado' => 'bloqueado',
                        default => 'plano',
                    };

                    CompanyModule::updateOrCreate(
                        [
                            'company_id' => $license->company_id,
                            'module_id' => $planModule->module_id,
                        ],
                        [
                            'plan_id' => $license->plan_id,
                            'tipo_contratacao' => $tipo,
                            'valor_mensal_adicional' => $planModule->valor_adicional ?? 0,
                            'data_inicio' => $license->inicio,
                            'data_fim' => $license->vencimento,
                            'status' => $status,
                            'observacoes' => 'Materializado automaticamente a partir do plano da licença.',
                        ],
                    );
                });
        });
    }
}
