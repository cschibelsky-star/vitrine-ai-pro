<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Quality\Services\FactoryCoreBootstrapGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class FactoryInstallCommand extends Command
{
    protected $signature = 'factory:install
        {--apply : Executa bootstrap no ambiente nao produtivo autorizado}
        {--confirm= : Confirma exatamente FACTORY_CORE_BOOTSTRAP}
        {--seed : Executa seed somente durante bootstrap autorizado}
        {--no-sync : Nao sincroniza padroes apos migrate}';

    protected $description = 'Planeja a instalacao do Factory Core; aplicar exige opt-in e confirmacao.';

    public function handle(FactoryCoreBootstrapGuard $guard): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->line('PLAN ONLY: factory:install nao executou migrations, sync, seed ou health.');
            $this->line('Etapas propostas: migrate; factory:sync (salvo --no-sync); db:seed (se --seed); factory:health.');
            $this->warn('Aplicacao real bloqueada sem --apply, confirmacao explicita e habilitacao no servidor HML.');
            return self::SUCCESS;
        }

        try {
            $guard->assertAllowed(
                $apply,
                (string) $this->option('confirm'),
                (string) app()->environment(),
                config('factory.core_bootstrap_enabled', false) === true,
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $steps = [['migrate', ['--force' => true]]];
        if (! $this->option('no-sync')) {
            $steps[] = ['factory:sync', []];
        }
        if ($this->option('seed')) {
            $steps[] = ['db:seed', [
                '--class' => 'Database\\Seeders\\FactoryCoreSeeder',
                '--force' => true,
            ]];
        }
        $steps[] = ['factory:health', []];

        foreach ($steps as [$command, $args]) {
            $exitCode = Artisan::call($command, $args, $this->output);
            if ($exitCode !== self::SUCCESS) {
                $this->error("Factory Core bootstrap interrompido em {$command} (exit {$exitCode}).");
                return self::FAILURE;
            }
        }

        $this->info('Factory Core bootstrap concluido no ambiente autorizado.');
        return self::SUCCESS;
    }
}
