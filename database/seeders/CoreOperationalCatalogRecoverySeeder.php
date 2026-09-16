<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\License;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Seeder;

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

            License::updateOrCreate(
                ['company_id' => $company->id, 'product_id' => $product->id],
                [
                    'plano' => $item['plano'],
                    'valor' => $item['valor'],
                    'inicio' => now()->toDateString(),
                    'vencimento' => now()->addMonths($item['months'])->toDateString(),
                    'status' => $item['status'],
                ],
            );
        }

        $this->call([
            ModuleSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
