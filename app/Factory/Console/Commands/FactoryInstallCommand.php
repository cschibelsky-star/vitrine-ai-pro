<?php
declare(strict_types=1);
namespace App\Factory\Console\Commands;
use Illuminate\Console\Command; use Illuminate\Support\Facades\Artisan;
class FactoryInstallCommand extends Command { protected $signature='factory:install {--seed} {--no-sync}'; protected $description='Instala o Factory Core.'; public function handle(): int { Artisan::call('migrate',['--force'=>true],$this->output); if(! $this->option('no-sync')) Artisan::call('factory:sync',[],$this->output); if($this->option('seed')) Artisan::call('db:seed',['--class'=>'Database\\Seeders\\FactoryCoreSeeder','--force'=>true],$this->output); Artisan::call('factory:health',[],$this->output); return self::SUCCESS; } }
