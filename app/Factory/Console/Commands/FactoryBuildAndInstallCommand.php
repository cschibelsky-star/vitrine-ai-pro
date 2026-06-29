<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\FinalMaster\Services\FactoryFinalMasterService;
use Illuminate\Console\Command;
use Throwable;

class FactoryBuildAndInstallCommand extends Command
{
    protected $signature = 'factory:build-and-install
        {request* : Solicitação livre do sistema}
        {--dry-run : Simula a instalação sem copiar para o projeto}
        {--force : Sobrescreve arquivos existentes com backup quando suportado}
        {--migrate : Executa migrations ao final da instalação real}';

    protected $description = 'Executa o fluxo final completo da Factory em um único comando.';

    public function handle(FactoryFinalMasterService $service): int
    {
        $request = implode(' ', (array) $this->argument('request'));

        try {
            $report = $service->buildAndInstall(
                $request,
                (bool) $this->option('dry-run'),
                (bool) $this->option('force'),
                (bool) $this->option('migrate'),
            );
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Final Master concluída.');
        $this->line('Status: ' . $report['status']);
        $this->line('Modo: ' . $report['mode']);
        $this->line('Blueprint: ' . ($report['blueprint'] ?? '-'));
        $this->line('Relatório: ' . $report['path']);
        $this->warn($report['final_note']);

        $this->table(['Etapa', 'Arquivo'], array_map(
            fn ($step, $path) => [$step, $path],
            array_keys($report['steps']),
            $report['steps']
        ));

        return $report['status'] === 'finished' ? self::SUCCESS : self::FAILURE;
    }
}
