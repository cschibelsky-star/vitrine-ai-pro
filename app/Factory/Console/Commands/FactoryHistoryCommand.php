<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\History\Services\BuildHistoryService;
use Illuminate\Console\Command;

class FactoryHistoryCommand extends Command
{
    protected $signature = 'factory:history {--record=}';
    protected $description = 'Lista ou registra eventos no histórico da Factory.';

    public function handle(BuildHistoryService $history): int
    {
        if ($event = $this->option('record')) {
            $path = $history->record((string) $event);
            $this->info('Evento registrado: ' . $path);
            return self::SUCCESS;
        }

        $files = $history->list();

        $this->info('Build History');
        $this->table(['Arquivo'], array_map(fn ($file) => [$file], $files));

        return self::SUCCESS;
    }
}
