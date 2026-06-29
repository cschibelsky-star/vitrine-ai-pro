<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $produtosPlanos = [
            'TV Digital Enterprise' => [['nome'=>'Start','valor'=>497.00,'ciclo'=>'mensal'],['nome'=>'Pro','valor'=>997.00,'ciclo'=>'mensal'],['nome'=>'Enterprise','valor'=>1500.00,'ciclo'=>'mensal'],['nome'=>'White Label','valor'=>2500.00,'ciclo'=>'mensal']],
            'Portal News AI' => [['nome'=>'Start','valor'=>497.00,'ciclo'=>'mensal'],['nome'=>'Pro','valor'=>997.00,'ciclo'=>'mensal'],['nome'=>'Enterprise','valor'=>1500.00,'ciclo'=>'mensal']],
            'Visite Cidade' => [['nome'=>'Beta','valor'=>0.00,'ciclo'=>'trial'],['nome'=>'Start','valor'=>497.00,'ciclo'=>'mensal'],['nome'=>'Pro','valor'=>997.00,'ciclo'=>'mensal'],['nome'=>'Governo','valor'=>1500.00,'ciclo'=>'mensal']],
            'SISMED' => [['nome'=>'Trial','valor'=>0.00,'ciclo'=>'trial'],['nome'=>'Implantação','valor'=>0.00,'ciclo'=>'implantacao'],['nome'=>'Enterprise','valor'=>2500.00,'ciclo'=>'mensal']],
            'Município Digital IA' => [['nome'=>'Implantação','valor'=>0.00,'ciclo'=>'implantacao'],['nome'=>'Governo','valor'=>2500.00,'ciclo'=>'mensal'],['nome'=>'Enterprise','valor'=>4500.00,'ciclo'=>'mensal']],
            'TV Digital White Label' => [['nome'=>'White Label','valor'=>2500.00,'ciclo'=>'mensal'],['nome'=>'Agência','valor'=>3500.00,'ciclo'=>'mensal'],['nome'=>'Enterprise','valor'=>5000.00,'ciclo'=>'mensal']],
        ];
        foreach ($produtosPlanos as $nomeProduto => $planos) {
            $produto = Product::firstOrCreate(['nome'=>$nomeProduto], ['categoria'=>'SaaS','status'=>'Ativo']);
            foreach ($planos as $planoData) {
                Plan::updateOrCreate(['product_id'=>$produto->id,'nome'=>$planoData['nome']], ['valor_mensal'=>$planoData['valor'],'valor_implantacao'=>0.00,'ciclo_cobranca'=>$planoData['ciclo'],'status'=>'Ativo']);
            }
        }
    }
}
