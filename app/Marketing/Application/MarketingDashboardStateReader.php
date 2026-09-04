<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Infrastructure\Persistence\Models\MarketingCampaign;
use Illuminate\Support\Facades\Schema;

final class MarketingDashboardStateReader
{
    /** @return array<string, mixed> */
    public function latest(): array
    {
        if (! Schema::hasTable('marketing_campaigns') || ! Schema::hasTable('marketing_campaign_tasks')) {
            return $this->unavailable('persistence_not_ready');
        }

        $campaign = MarketingCampaign::query()
            ->with(['tasks.agentDefinition', 'artifacts'])
            ->latest('id')
            ->first();

        if ($campaign === null) {
            return $this->unavailable('no_campaigns');
        }

        $tasks = $campaign->tasks->mapWithKeys(function ($task): array {
            $agentId = (string) (
                $task->agentDefinition?->agent_id
                ?? $task->agentDefinition?->slug
                ?? $task->task_key
            );

            return [$agentId => [
                'agent_id' => $agentId,
                'task_key' => (string) $task->task_key,
                'status' => (string) $task->status,
                'attempts' => (int) $task->attempt,
                'started_at' => $task->started_at?->toISOString(),
                'completed_at' => $task->completed_at?->toISOString(),
                'blocked_reason' => $task->blocked_reason,
            ]];
        })->all();

        $statusCounts = collect($tasks)
            ->countBy(static fn (array $task): string => (string) $task['status'])
            ->all();

        return [
            'available' => true,
            'source' => 'marketing_persistence',
            'campaign' => [
                'id' => $campaign->getKey(),
                'public_id' => (string) ($campaign->public_id ?? ''),
                'name' => (string) ($campaign->name ?? ''),
                'objective' => (string) ($campaign->objective ?? ''),
                'status' => (string) ($campaign->status ?? 'unknown'),
                'started_at' => $campaign->started_at?->toISOString(),
                'completed_at' => $campaign->completed_at?->toISOString(),
            ],
            'tasks' => $tasks,
            'status_counts' => $statusCounts,
            'artifact_count' => $campaign->artifacts->count(),
            'blocked_tasks' => collect($tasks)
                ->filter(static fn (array $task): bool => $task['status'] === 'blocked')
                ->values()
                ->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function unavailable(string $reason): array
    {
        return [
            'available' => false,
            'source' => 'marketing_persistence',
            'reason' => $reason,
            'campaign' => null,
            'tasks' => [],
            'status_counts' => [],
            'artifact_count' => null,
            'blocked_tasks' => [],
        ];
    }
}
