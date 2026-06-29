<?php

namespace Database\Seeders;

use App\Models\Fornecedor;
use Illuminate\Database\Seeder;

class FornecedorSeeder extends Seeder
{
    public function run(): void
    {
        Fornecedor::query()->firstOrCreate(['nome' => 'Fornecedores Demonstração'], [
            'documento' => '00000000000',
            'email' => 'demo@example.com',
            'telefone' => '',
            'cidade' => 'Sumaré',
            'status' => 'active',
        ]);
    }
}
