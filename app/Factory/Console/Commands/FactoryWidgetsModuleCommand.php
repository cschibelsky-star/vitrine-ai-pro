<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Dashboard\Services\WidgetIntelligenceService;
use Illuminate\Console\Command;
use Throwable;

class FactoryWidgetsModuleCommand extends Command
{
    protected $signature = 'factory:widgets-module {slug : Slug do módulo gerado}';
    protected $description = 'Gera widgets inteligentes para um módulo.';

    public function handle(WidgetIntelligenceService $widgets): int
    {
        try {
            $result = $widgets->generate((string) $this->argument('slug'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Widgets inteligentes gerados.');
        $this->line('Módulo: ' . $result['module']);
        $this->line('Arquivo: ' . $result['path']);

        $this->table(['Widget', 'Tipo', 'Motivo'], array_map(fn ($widget) => [
            $widget['label'],
            $widget['type'],
            $widget['reason'],
        ], $result['widgets']));

        return self::SUCCESS;
    }
}
