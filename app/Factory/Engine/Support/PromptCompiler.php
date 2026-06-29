<?php

declare(strict_types=1);

namespace App\Factory\Engine\Support;

class PromptCompiler
{
    public function compile(array $context): string
    {
        $blueprint = $context['blueprint'] ?? [];
        $input = $context['input'] ?? [];

        $title = $input['title'] ?? $input['task'] ?? 'Execução Factory';
        $briefing = $input['briefing'] ?? $input['context']['module'] ?? 'Sem briefing informado.';

        return trim(
            "Tarefa: {$title}\n\n" .
            "Briefing: {$briefing}\n\n" .
            "Blueprint: " . json_encode($blueprint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
