<?php

declare(strict_types=1);

namespace App\Factory\Architecture\Services;

use App\Factory\AI\Services\AdvancedRequirementAnalyzer;

class DomainKnowledgeEngine
{
    public function __construct(
        protected AdvancedRequirementAnalyzer $analyzer,
    ) {
    }

    public function analyze(string $input): array
    {
        $blueprint = $this->analyzer->analyze($input);
        $domain = (string) ($blueprint['architecture']['domain'] ?? 'generico');
        $modules = array_values(array_map(
            fn (array $module): string => (string) ($module['slug'] ?? ''),
            $blueprint['modules'] ?? [],
        ));

        return [
            'input' => $input,
            'domain' => $domain,
            'label' => (string) ($blueprint['name'] ?? 'Sistema Gerado pela Factory'),
            'modules' => array_values(array_filter($modules)),
            'typical_relationships' => $this->relationships($blueprint['modules'] ?? []),
            'recommended_dashboards' => $this->dashboards($blueprint['modules'] ?? []),
            'detected_at' => now()->toISOString(),
        ];
    }

    public function detectDomain(string $text): string
    {
        $blueprint = $this->analyzer->analyze($text);

        return (string) ($blueprint['architecture']['domain'] ?? 'generico');
    }

    protected function relationships(array $modules): array
    {
        $relationships = [];

        foreach ($modules as $module) {
            foreach (($module['fields'] ?? []) as $field) {
                if (($field['type'] ?? null) !== 'foreignId') {
                    continue;
                }

                $relationships[] = ($module['slug'] ?? 'modulo') . '.' . ($field['name'] ?? 'id')
                    . ' belongsTo ' . ($field['related_model'] ?? 'Registro');
            }
        }

        return array_values(array_unique($relationships));
    }

    protected function dashboards(array $modules): array
    {
        $metrics = [];

        foreach ($modules as $module) {
            foreach (($module['dashboard_metrics'] ?? []) as $metric) {
                $metrics[] = ($module['slug'] ?? 'modulo') . ':' . $metric;
            }
        }

        return array_values(array_unique($metrics));
    }
}
