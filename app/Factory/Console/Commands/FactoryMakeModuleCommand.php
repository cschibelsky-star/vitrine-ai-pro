<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Builder\Services\ModuleBlueprintFactory;
use App\Factory\Builder\Services\ModuleBuilder;
use Illuminate\Console\Command;

class FactoryMakeModuleCommand extends Command
{
    protected $signature = 'factory:make-module {name : Nome do módulo a gerar}';

    protected $description = 'Gera um módulo Laravel + Filament em modo seguro dentro de storage/app/factory/builds.';

    public function handle(ModuleBlueprintFactory $blueprintFactory, ModuleBuilder $builder): int
    {
        $name = (string) $this->argument('name');

        $blueprint = $blueprintFactory->make($name);
        $path = $builder->build($blueprint);

        $this->info('Módulo gerado com sucesso.');
        $this->line('Nome: ' . $blueprint->name);
        $this->line('Model: ' . $blueprint->modelName);
        $this->line('Tabela: ' . $blueprint->tableName);
        $this->line('Local: ' . $path);

        return self::SUCCESS;
    }
}
