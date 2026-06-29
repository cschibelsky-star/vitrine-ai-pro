<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\Engine\Services\FactoryEngine;
use App\Factory\Models\FactoryBlueprint;
use App\Factory\Models\FactoryCapability;
use App\Factory\Models\FactoryExecution;
use App\Factory\Models\FactoryProject;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FactoryEngineTestCommand extends Command
{
    protected $signature = 'factory:engine-test';

    protected $description = 'Executa um teste interno do Factory Engine.';

    public function handle(FactoryEngine $engine): int
    {
        $project = FactoryProject::query()->first();
        $capability = FactoryCapability::query()->first();
        $blueprint = FactoryBlueprint::query()->first();

        if (! $project || ! $capability || ! $blueprint) {
            $this->error('Dados base ausentes. Execute php artisan factory:sync.');

            return self::FAILURE;
        }

        $execution = FactoryExecution::query()->create([
            'uuid' => (string) Str::uuid(),
            'factory_project_id' => $project->id,
            'factory_capability_id' => $capability->id,
            'factory_blueprint_id' => $blueprint->id,
            'name' => 'Teste Factory Engine',
            'status' => 'pending',
            'attempt' => 1,
            'input' => [
                'title' => 'Teste interno do Factory Engine',
                'briefing' => 'Validar execução simulada pelo InternalProvider.',
                'audience' => 'administradores',
            ],
            'context' => [
                'provider' => 'internal',
                'source' => 'factory_engine_test_command',
            ],
        ]);

        $engine->execute($execution);

        $execution->refresh();

        $this->info('Factory Engine executado.');
        $this->line('Execution ID: ' . $execution->id);
        $this->line('Status: ' . $execution->status);

        return $execution->status === 'finished' ? self::SUCCESS : self::FAILURE;
    }
}
