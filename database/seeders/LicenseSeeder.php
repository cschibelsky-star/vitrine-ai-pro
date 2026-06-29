<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\License;
use App\Models\Product;
use Illuminate\Database\Seeder;

class LicenseSeeder extends Seeder
{
    public function run(): void
    {
        $licenses = [
            ['company' => 'TV Sumaré', 'product' => 'TV Digital Enterprise', 'plano' => 'Enterprise', 'valor' => 1500.00, 'inicio' => now(), 'vencimento' => now()->addYear(), 'status' => 'Ativa'],
            ['company' => 'Visite Sumaré', 'product' => 'Visite Cidade', 'plano' => 'Beta', 'valor' => 0.00, 'inicio' => now(), 'vencimento' => now()->addMonths(3), 'status' => 'Homologação'],
            ['company' => 'SISMED', 'product' => 'SISMED', 'plano' => 'Desenvolvimento', 'valor' => 0.00, 'inicio' => now(), 'vencimento' => now()->addMonths(1), 'status' => 'Trial'],
        ];

        foreach ($licenses as $item) {
            $company = Company::where('nome', $item['company'])->first();
            $product = Product::where('nome', $item['product'])->first();

            if ($company && $product) {
                License::updateOrCreate(
                    ['company_id' => $company->id, 'product_id' => $product->id],
                    ['plano' => $item['plano'], 'valor' => $item['valor'], 'inicio' => $item['inicio'], 'vencimento' => $item['vencimento'], 'status' => $item['status']]
                );
            }
        }
    }
}
