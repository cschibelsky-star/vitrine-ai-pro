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
        {--install : Executa instalação real; sem esta opção o comando é sempre dry-run}
        {--force : Sobrescreve arquivos existentes com backup quando suportado}
        {--migrate : Executa migrations ao final da instalação real}
        {--confirm-production : Confirma explicitamente escrita no projeto e banco}
        {--idempotency-key= : Identificador estável da solicitação}';

    protected $description = 'Executa o fluxo final completo da Factory em um único comando.';

    public function handle(FactoryFinalMasterService $service): int
    {
        $request = implode(' ', (array) $this->argument('request'));
        $install = (bool) $this->option('install');
        $migrate = (bool) $this->option('migrate');

        if (($install || $migrate) && ! (bool) $this->option('confirm-production')) {
            $this->error('Instalação real exige --confirm-production. Nenhum arquivo foi alterado.');
            return self::INVALID;
        }

        if ($migrate && ! $install) {
            $this->error('--migrate exige --install.');
            return self::INVALID;
        }

        try {
            $report = $service->buildAndInstall(
                $request,
                ! $install,
                (bool) $this->option('force'),
                $migrate,
                $this->option('idempotency-key') ? (string) $this->option('idempotency-key') : null,
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
