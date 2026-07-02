<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@vitrineaipro.com.br'],
            [
                'name' => 'Administrador Vitrine AI Pro',
                'password' => Hash::make('password'),
            ]
        );
    }
}
