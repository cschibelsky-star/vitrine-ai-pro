<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            ['empresa' => 'Vitrine AI Pro', 'logo' => null, 'telefone' => '(19) 99999-0000', 'email' => 'contato@vitrineaipro.com.br', 'endereco' => 'Sumaré - SP']
        );
    }
}
