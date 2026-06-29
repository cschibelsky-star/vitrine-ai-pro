<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProductSeeder::class,
            CompanySeeder::class,
            LicenseSeeder::class,
            PaymentSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
