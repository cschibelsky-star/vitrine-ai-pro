<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

class ViaSentinelCollector
{
    public function __construct(
        private readonly ViaFactoryContextCollector $factoryContext,
        private readonly AiUsageBudgetService $budget,
        private readonly ViaEcosystemContextCollector $ecosystem,
    ) {
    }

    public function collect(): array
    {
        $factory = $this->factoryContext->collect();
        $budget = $this->budget->summary();
        $ecosystem = $this->ecosystem->collect();

        $snapshots = [
            [
                'domain' => 'factory',
                'source' => 'factory_context',
                'project_id' => data_get($factory, 'latest_intake.project_slug'),
                'status' => $this->factoryStatus($factory),
                'metrics' => [
                    'intake_entries' => (int) data_get($factory, 'sources.commercial_intake_storage.entries', 0),
                    'latest_report_exit_code' => data_get($factory, 'latest_report.exit_code'),
                    'latest_report_status' => data_get($factory, 'latest_report.status'),
                ],
                'evidence' => [
                    'schema_assessment' => data_get($factory, 'schema_assessment', []),
                    'latest_report' => data_get($factory, 'latest_report', []),
                    'security' => data_get($factory, 'sources.intake_security', []),
                ],
            ],
            [
                'domain' => 'ai',
                'source' => 'ai_budget',
                'project_id' => null,
                'status' => $this->budgetStatus($budget),
                'metrics' => [
                    'provider_cost_brl' => (float) ($budget['provider_cost_brl'] ?? 0),
                    'monthly_limit_brl' => (float) ($budget['monthly_limit_brl'] ?? 0),
                    'remaining_brl' => $budget['remaining_brl'] ?? null,
                    'usage_percent' => $this->budgetPercent($budget),
                ],
                'evidence' => $budget,
            ],
        ];

        foreach ((array) ($ecosystem['snapshots'] ?? []) as $snapshot) {
            if (is_array($snapshot)) {
                $snapshots[] = $snapshot;
            }
        }

        if ((bool) ($ecosystem['enabled'] ?? true) && ! (bool) ($ecosystem['available'] ?? false)) {
            $snapshots[] = [
                'domain' => 'ecosystem',
                'source' => 'ecosystem.source',
                'project_id' => null,
                'status' => (string) ($ecosystem['status'] ?? 'attention'),
                'metrics' => [
                    'available' => false,
                    'http_status' => $ecosystem['http_status'] ?? null,
                ],
                'evidence' => [
                    'reason' => $ecosystem['reason'] ?? 'unknown',
                ],
            ];
        }

        return $snapshots;
    }

    private function factoryStatus(array $factory): string
    {
        $reportStatus = strtolower((string) data_get($factory, 'latest_report.status', ''));
        $exitCode = data_get($factory, 'latest_report.exit_code');
        $schemaMatches = (bool) data_get($factory, 'schema_assessment.latest_intake_matches_current_schema', true)
            && (bool) data_get($factory, 'schema_assessment.latest_report_matches_current_schema', true);

        if ($exitCode !== null && (int) $exitCode !== 0) {
            return 'alert';
        }
        if (in_array($reportStatus, ['failed', 'error', 'blocked'], true)) {
            return 'alert';
        }
        if (! $schemaMatches) {
            return 'attention';
        }

        return 'normal';
    }

    private function budgetStatus(array $budget): string
    {
        $percent = $this->budgetPercent($budget);
        if ($percent === null) {
            return 'normal';
        }

        $alert = (float) config('via_sentinel.ai_budget_alert_percent', 95);
        $attention = (float) config('via_sentinel.ai_budget_attention_percent', 80);

        if ($percent >= $alert) {
            return 'alert';
        }
        if ($percent >= $attention) {
            return 'attention';
        }

        return 'normal';
    }

    private function budgetPercent(array $budget): ?float
    {
        $limit = (float) ($budget['monthly_limit_brl'] ?? 0);
        if ($limit <= 0) {
            return null;
        }

        $spent = (float) ($budget['provider_cost_brl'] ?? 0);
        return round(($spent / $limit) * 100, 2);
    }
}
