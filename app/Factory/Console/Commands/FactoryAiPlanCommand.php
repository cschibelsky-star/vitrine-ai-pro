<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\AI\Services\AdvancedRequirementAnalyzer;
use Illuminate\Console\Command;
use Throwable;

class FactoryAiPlanCommand extends Command
{
    protected $signature = 'factory:ai-plan {prompt* : Descrição do sistema em linguagem natural}';

    protected $description = 'Mostra o plano arquitetural que a Factory criaria a partir de uma descrição.';

    public function handle(AdvancedRequirementAnalyzer $analyzer): int
    {
        $prompt = implode(' ', (array) $this->argument('prompt'));

        try {
            $blueprint = $analyzer->analyze($prompt);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Plano arquitetural gerado pela Factory IA');
        $this->line('Sistema: ' . $blueprint['name']);
        $this->line('Slug: ' . $blueprint['slug']);
        $this->line('Domínio: ' . ($blueprint['architecture']['domain'] ?? 'n/d'));
        $this->line('Módulos: ' . count($blueprint['modules']));
        $this->line('Relacionamentos: ' . ($blueprint['architecture']['relationships_count'] ?? 0));

        $rows = [];

        foreach ($blueprint['modules'] as $module) {
            $relationships = collect($module['fields'])
                ->where('type', 'foreignId')
                ->map(fn ($field) => $field['name'] . ' → ' . ($field['related_model'] ?? '-'))
                ->implode(', ');

            $rows[] = [
                $module['slug'],
                $module['label'],
                count($module['fields']),
                $relationships ?: '-',
            ];
        }

        $this->table(['Módulo', 'Label', 'Campos', 'Relacionamentos'], $rows);

        return self::SUCCESS;
    }
}
