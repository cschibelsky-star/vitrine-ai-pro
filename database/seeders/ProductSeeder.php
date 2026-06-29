<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['nome' => 'TV Digital Enterprise', 'categoria' => 'Mídia', 'descricao' => 'Portal e TV digital empresarial.', 'status' => 'Ativo'],
            ['nome' => 'Portal News AI', 'categoria' => 'Mídia', 'descricao' => 'Portal de notícias com automação por IA.', 'status' => 'Ativo'],
            ['nome' => 'Visite Cidade', 'categoria' => 'Turismo', 'descricao' => 'Aplicativo/portal de turismo municipal.', 'status' => 'Ativo'],
            ['nome' => 'SISMED', 'categoria' => 'Saúde', 'descricao' => 'Sistema de saúde e gestão operacional.', 'status' => 'Ativo'],
            ['nome' => 'Município Digital IA', 'categoria' => 'Governo', 'descricao' => 'Solução institucional para governo digital.', 'status' => 'Ativo'],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['nome' => $product['nome']], $product);
        }
    }
}
