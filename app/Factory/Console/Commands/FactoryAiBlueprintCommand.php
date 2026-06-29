<?php

declare(strict_types=1);

namespace App\Factory\Console\Commands;

use App\Factory\AI\Services\AdvancedRequirementAnalyzer;
use App\Factory\AI\Services\AiBlueprintWriter;
use Illuminate\Console\Command;
use Throwable;

class FactoryAiBlueprintCommand extends Command
{
    protected $signature = 'factory:ai-blueprint {prompt* : Descrição do sistema em linguagem natural}';

    protected $description = 'Gera um blueprint avançado de sistema a partir de uma descrição em linguagem natural.';

    public function handle(AdvancedRequirementAnalyzer $analyzer, AiBlueprintWriter $writer): int
    {
        $prompt = implode(' ', (array) $this->argument('prompt'));

        try {
            $blueprint = $analyzer->analyze($prompt);
            $path = $writer->write($blueprint);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Blueprint IA avançado gerado com sucesso.');
        $this->line('Nome: ' . $blueprint['name']);
        $this->line('Slug: ' . $blueprint['slug']);
        $this->line('Domínio: ' . ($blueprint['architecture']['domain'] ?? 'n/d'));
        $this->line('Módulos: ' . count($blueprint['modules']));
        $this->line('Local: ' . $path);

        $this->newLine();
        $this->warn('Próximo comando sugerido:');
        $this->line('php artisan factory:make-system ' . $blueprint['slug']);

        return self::SUCCESS;
    }
}
