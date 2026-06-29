<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['nome' => 'TV Sumaré', 'responsavel' => 'Administração', 'cidade' => 'Sumaré', 'estado' => 'SP', 'produto_principal' => 'TV Digital Enterprise', 'status' => 'Ativo'],
            ['nome' => 'Visite Sumaré', 'responsavel' => 'Administração', 'cidade' => 'Sumaré', 'estado' => 'SP', 'produto_principal' => 'Visite Cidade', 'status' => 'Homologação'],
            ['nome' => 'SISMED', 'responsavel' => 'Administração', 'cidade' => 'Sumaré', 'estado' => 'SP', 'produto_principal' => 'SISMED', 'status' => 'Implantação'],
        ];

        foreach ($companies as $company) {
            Company::updateOrCreate(['nome' => $company['nome']], $company);
        }
    }
}
