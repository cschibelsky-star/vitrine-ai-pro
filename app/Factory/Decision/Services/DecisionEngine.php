<?php

declare(strict_types=1);

namespace App\Factory\Decision\Services;

use App\Factory\AI\Services\AdvancedRequirementAnalyzer;

class DecisionEngine
{
    public function __construct(
        protected AdvancedRequirementAnalyzer $analyzer,
    ) {
    }

    public function decide(string $prompt): array
    {
        $blueprint = $this->analyzer->analyze($prompt);
        $domain = (string) ($blueprint['architecture']['domain'] ?? 'generico');
        $modules = array_values(array_filter(array_map(
            fn (array $module): string => (string) ($module['slug'] ?? ''),
            $blueprint['modules'] ?? [],
        )));

        return [
            'domain' => $domain,
            'modules' => $modules,
            'components' => $this->components($modules),
            'dashboards' => ['executive', 'operational', 'quality'],
            'qa_level' => 'strict',
            'install_mode' => 'safe',
            'decision_reason' => 'Domínio e módulos derivados do Factory Intelligence Core.',
            'decided_at' => now()->toISOString(),
        ];
    }

    protected function components(array $modules): array
    {
        $components = ['dashboard', 'audit_log', 'timeline', 'smart_qa'];

        foreach ($modules as $module) {
            if (str_contains($module, 'document')) {
                $components[] = 'document_upload';
                $components[] = 'checklist';
            }

            if (str_contains($module, 'matching')) {
                $components[] = 'matching_engine';
            }

            if (str_contains($module, 'oportunidade')) {
                $components[] = 'radar';
            }

            if (str_contains($module, 'plano')) {
                $components[] = 'workflow';
            }

            if (str_contains($module, 'evento')) {
                $components[] = 'event_calendar';
            }

            if (str_contains($module, 'video')) {
                $components[] = 'video_library';
            }
        }

        return array_values(array_unique($components));
    }
}
