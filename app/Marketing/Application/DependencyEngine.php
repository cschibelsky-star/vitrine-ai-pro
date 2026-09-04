<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Domain\Campaigns\CampaignState;
use App\Marketing\Domain\Campaigns\CampaignStatus;
use App\Marketing\Domain\Tasks\TaskStatus;

final class DependencyEngine
{
    /** @return list<string> */
    public function refresh(CampaignState $campaign): array
    {
        if (in_array($campaign->status(), [
            CampaignStatus::Paused,
            CampaignStatus::Blocked,
            CampaignStatus::Completed,
            CampaignStatus::Cancelled,
        ], true)) {
            return [];
        }

        $ready = [];

        foreach ($campaign->tasks() as $agentId => $task) {
            if (($task['status'] ?? null) !== TaskStatus::Pending) {
                continue;
            }

            if (! $campaign->allDependenciesCompleted($agentId)) {
                continue;
            }

            $campaign->markTaskReady($agentId);
            $ready[] = $agentId;
        }

        if ($ready !== [] && $campaign->status() === CampaignStatus::Draft) {
            $campaign->markReady();
        }

        return $ready;
    }

    public function canStart(CampaignState $campaign, string $agentId): bool
    {
        if (in_array($campaign->status(), [
            CampaignStatus::Paused,
            CampaignStatus::Blocked,
            CampaignStatus::Completed,
            CampaignStatus::Cancelled,
        ], true)) {
            return false;
        }

        $task = $campaign->task($agentId);

        return ($task['status'] ?? null) === TaskStatus::Ready
            && $campaign->allDependenciesCompleted($agentId);
    }

    /** @return list<string> */
    public function executableTaskIds(CampaignState $campaign): array
    {
        $this->refresh($campaign);

        $executable = [];

        foreach ($campaign->tasks() as $agentId => $task) {
            if (($task['status'] ?? null) === TaskStatus::Ready && $this->canStart($campaign, $agentId)) {
                $executable[] = $agentId;
            }
        }

        return $executable;
    }
}
