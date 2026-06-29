<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Builder\Services\ModuleInstaller;
use Illuminate\Console\Command;
use Throwable;

class FactoryInstallModuleCommand extends Command
{
    protected $signature = 'factory:install-module
        {slug : Slug do módulo gerado em storage/app/factory/builds}
        {--dry-run : Apenas simula a instalação}
        {--force : Sobrescreve arquivos existentes}';

    protected $description = 'Instala um módulo gerado pela Factory no projeto Laravel real.';

    public function handle(ModuleInstaller $installer): int
    {
        $slug = (string) $this->argument('slug');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        try {
            $results = $installer->install($slug, $dryRun, $force);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Simulação executada. Nenhum arquivo foi copiado.');
        } else {
            $this->info('Instalação executada.');
        }

        $rows = [];

        foreach ($results as $result) {
            $rows[] = [
                $result['status'],
                str_replace(base_path() . '/', '', $result['source']),
                str_replace(base_path() . '/', '', $result['target']),
            ];
        }

        $this->table(['Status', 'Origem', 'Destino'], $rows);

        $copied = collect($results)->where('status', 'copied')->count();
        $skipped = collect($results)->where('status', 'skipped_exists')->count();

        $this->line("Copiados: {$copied}");
        $this->line("Ignorados por já existir: {$skipped}");

        if (! $dryRun) {
            $this->newLine();
            $this->warn('Próximos comandos recomendados:');
            $this->line('composer dump-autoload');
            $this->line('php artisan optimize:clear');
            $this->line('php artisan migrate');
        }

        return self::SUCCESS;
    }
}
