<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Blueprint\Services\BlueprintRepository;
use App\Factory\Blueprint\Services\DefaultBlueprintFactory;
use Illuminate\Console\Command;
use Throwable;

class FactoryMakeBlueprintCommand extends Command
{
    protected $signature = 'factory:make-blueprint {slug : Slug do blueprint}';
    protected $description = 'Gera um blueprint de sistema para a Factory.';

    public function handle(DefaultBlueprintFactory $factory, BlueprintRepository $repository): int
    {
        try {
            $blueprint = $factory->make((string) $this->argument('slug'));
            $path = $repository->save($blueprint);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Blueprint gerado com sucesso.');
        $this->line('Nome: ' . $blueprint->name);
        $this->line('Slug: ' . $blueprint->slug);
        $this->line('Local: ' . $path);
        $this->line('Módulos: ' . count($blueprint->modules));

        return self::SUCCESS;
    }
}
