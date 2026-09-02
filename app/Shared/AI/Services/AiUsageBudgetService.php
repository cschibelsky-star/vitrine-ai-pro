<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use App\Shared\AI\Models\AiConsumption;
use RuntimeException;

class AiUsageBudgetService
{
    public function assertProjectWithinBudget(string $projectId): void
    {
        $limit = $this->projectLimit($projectId);

        if ($limit <= 0) {
            return;
        }

        $spent = $this->projectSpent($projectId);

        if ($spent >= $limit) {
            throw new RuntimeException('Limite mensal de IA do projeto atingido.');
        }
    }

    public function summary(?string $projectId = null): array
    {
        if ($projectId !== null && $projectId !== '') {
            $spent = $this->projectSpent($projectId);
            $limit = $this->projectLimit($projectId);

            return [
                'scope' => 'project',
                'project_id' => $projectId,
                'month' => now()->format('Y-m'),
                'provider_cost_brl' => round($spent, 6),
                'monthly_limit_brl' => $limit,
                'remaining_brl' => $limit > 0 ? round(max(0, $limit - $spent), 6) : null,
                'limit_enabled' => $limit > 0,
            ];
        }

        $query = $this->monthlyQuery();
        $spent = (float) (clone $query)->sum('provider_cost_brl');
        $limit = (float) config('ai_dev_hub.monthly_limit_brl', 0);

        return [
            'scope' => 'global_internal_development',
            'month' => now()->format('Y-m'),
            'provider_cost_brl' => round($spent, 6),
            'monthly_limit_brl' => $limit,
            'remaining_brl' => $limit > 0 ? round(max(0, $limit - $spent), 6) : null,
            'limit_enabled' => $limit > 0,
        ];
    }

    public function projectLimit(string $projectId): float
    {
        $configured = (array) config('ai_dev_hub.project_monthly_limits_brl', []);

        if (array_key_exists($projectId, $configured)) {
            return max(0, (float) $configured[$projectId]);
        }

        return max(0, (float) config('ai_dev_hub.monthly_limit_brl', 0));
    }

    private function projectSpent(string $projectId): float
    {
        return (float) $this->monthlyQuery()
            ->where('project_id', $projectId)
            ->sum('provider_cost_brl');
    }

    private function monthlyQuery()
    {
        return AiConsumption::query()
            ->where('resource_type', 'like', 'internal_development.%')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }
}
