<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Learning\Services\ModuleLearningService;
use Illuminate\Console\Command;
use Throwable;

class FactoryLearnModuleCommand extends Command
{
    protected $signature = 'factory:learn-module {slug : Slug do módulo gerado}';
    protected $description = 'Registra aprendizado técnico sobre um módulo gerado pela Factory.';

    public function handle(ModuleLearningService $learning): int
    {
        try {
            $result = $learning->learn((string) $this->argument('slug'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Aprendizado registrado com sucesso.');
        $this->line('Módulo: ' . $result['slug']);
        $this->line('Arquivo: ' . $result['knowledge_path']);

        $this->table(['Item', 'Status'], collect($result['summary'])->map(fn ($v, $k) => [$k, $v ? 'sim' : 'não'])->values()->all());

        return self::SUCCESS;
    }
}
