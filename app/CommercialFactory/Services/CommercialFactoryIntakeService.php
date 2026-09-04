<?php

namespace App\CommercialFactory\Services;

use App\Factory\AI\Services\AdvancedRequirementAnalyzer;
use App\Factory\Decision\Services\DecisionEngine;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CommercialFactoryIntakeService
{
    public function __construct(
        protected CommercialProductResolver $resolver,
        protected AdvancedRequirementAnalyzer $analyzer,
        protected DecisionEngine $decisionEngine,
    ) {
    }

    public function intake(array $data, bool $dryRun = true): array
    {
        $resolved = $this->resolver->resolve((string) $data['product']);
        $product = $resolved['config'];
        $planKey = (string) ($data['plan'] ?? 'start');
        $plan = $product['plans'][$planKey] ?? reset($product['plans']);
        $clientSlug = Str::slug((string) $data['client'], '_');
        $projectSlug = $resolved['key'] . '_' . $clientSlug;
        $base = storage_path('app/factory/commercial-intake/' . date('Ymd_His') . '_' . $projectSlug);
        File::ensureDirectoryExists($base);

        $prompt = trim(($product['factory_prompt'] ?? '') . "\n\nCliente: " . ($data['client'] ?? '') . "\nPlano: " . ($plan['label'] ?? $planKey) . "\nDomínio: " . ($data['domain'] ?? ''));
        $analysis = $this->analyzer->analyze($prompt);
        $decision = $this->decisionEngine->decide($prompt);

        File::put($base . '/commercial_intake.json', json_encode([
            'client' => $data['client'],
            'product' => $resolved['name'],
            'plan' => $planKey,
            'project_slug' => $projectSlug,
            'prompt' => $prompt,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        File::put($base . '/intelligence_analysis.json', json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put($base . '/decision.json', json_encode($decision, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $report = [
            'status' => 'awaiting_approval',
            'project_slug' => $projectSlug,
            'commercial_status' => 'analysis_ready_for_review',
            'domain' => $analysis['architecture']['domain'] ?? 'generico',
            'path' => $base . '/commercial_factory_report.json',
            'next_stage' => 'approval',
            'build_triggered' => false,
            'created_at' => now()->toISOString(),
        ];

        File::put($report['path'], json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $report;
    }
}
