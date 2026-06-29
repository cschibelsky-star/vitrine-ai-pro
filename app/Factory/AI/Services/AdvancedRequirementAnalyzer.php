<?php

declare(strict_types=1);

namespace App\Factory\AI\Services;

use Illuminate\Support\Str;

class AdvancedRequirementAnalyzer
{
    public function __construct(
        protected DomainKnowledgeBase $knowledgeBase,
    ) {
    }

    public function analyze(string $prompt): array
    {
        $normalized = Str::of($prompt)->lower()->ascii()->toString();
        $domain = $this->knowledgeBase->match($normalized);
        $blueprint = $this->knowledgeBase->blueprintFor($domain, $prompt);

        $blueprint['architecture'] = [
            'domain' => $domain,
            'entities_count' => count($blueprint['modules'] ?? []),
            'relationships_count' => $this->countRelationships($blueprint),
            'recommended_stack' => [
                'backend' => 'Laravel',
                'admin' => 'Filament',
                'database' => 'MySQL',
            ],
            'qa_required' => true,
        ];

        return $blueprint;
    }

    protected function countRelationships(array $blueprint): int
    {
        $count = 0;

        foreach (($blueprint['modules'] ?? []) as $module) {
            foreach (($module['fields'] ?? []) as $field) {
                if (($field['type'] ?? null) === 'foreignId') {
                    $count++;
                }
            }
        }

        return $count;
    }
}
