<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\RealBuilder\Services\FinishProjectService;
use Illuminate\Console\Command;
use Throwable;

class FactoryFinishProjectCommand extends Command
{
    protected $signature = 'factory:finish-project {request* : Solicitação livre do projeto}';

    protected $description = 'Executa finalização completa: solicitação livre, produção e real build.';

    public function handle(FinishProjectService $service): int
    {
        $request = implode(' ', (array) $this->argument('request'));

        try {
            $report = $service->finish($request);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Factory Finish Project concluído.');
        $this->line('Blueprint: ' . $report['blueprint']);
        $this->line('Finalização: ' . $report['finalize_status']);
        $this->line('Real Build: ' . $report['real_build_report']);
        $this->warn('Próximo comando: ' . $report['next_command']);

        return $report['finalize_status'] === 'passed' ? self::SUCCESS : self::FAILURE;
    }
}
