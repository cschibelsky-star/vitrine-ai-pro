<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Blueprint\Services\BlueprintRepository;
use App\Factory\Blueprint\Services\SystemBuilder;
use Illuminate\Console\Command;
use Throwable;

class FactoryMakeSystemCommand extends Command
{
    protected $signature = 'factory:make-system {slug : Slug do blueprint salvo}';
    protected $description = 'Gera múltiplos módulos a partir de um blueprint de sistema.';

    public function handle(BlueprintRepository $repository, SystemBuilder $builder): int
    {
        try {
            $blueprint = $repository->find((string) $this->argument('slug'));
            $results = $builder->build($blueprint);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Sistema gerado com sucesso em modo seguro.');
        $this->line('Sistema: ' . $blueprint->name);

        $this->table(['Módulo', 'Model', 'Local'], array_map(fn ($result) => [
            $result['module'],
            $result['model'],
            $result['path'],
        ], $results));

        return self::SUCCESS;
    }
}
