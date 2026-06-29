<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $payments = [
            ['company' => 'TV Sumaré', 'valor' => 1500.00, 'vencimento' => now()->startOfMonth(), 'status' => 'Pago', 'observacao' => 'Mensalidade TV Digital Enterprise'],
            ['company' => 'Visite Sumaré', 'valor' => 800.00, 'vencimento' => now()->addDays(10), 'status' => 'Aberto', 'observacao' => 'Implantação Visite Cidade'],
            ['company' => 'SISMED', 'valor' => 0.00, 'vencimento' => now()->subDays(5), 'status' => 'Atrasado', 'observacao' => 'Período Trial SISMED'],
        ];

        foreach ($payments as $item) {
            $company = Company::where('nome', $item['company'])->first();

            if ($company) {
                Payment::updateOrCreate(
                    ['company_id' => $company->id, 'observacao' => $item['observacao']],
                    ['valor' => $item['valor'], 'vencimento' => $item['vencimento'], 'status' => $item['status']]
                );
            }
        }
    }
}
