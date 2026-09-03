<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Domain\Tasks\TaskStatus;
use InvalidArgumentException;

final class CampaignWorkflowState
{
    /** @var array<string, TaskStatus> */
    private array $tasks;

    /** @param list<string> $agentIds */
    public function __construct(
        public readonly string $campaignId,
        array $agentIds,
    ) {
        if ($campaignId === '') {
            throw new InvalidArgumentException('campaign_id is required.');
        }

        $this->tasks = array_fill_keys($agentIds, TaskStatus::Pending);
    }

    public function statusOf(string $agentId): TaskStatus
    {
        return $this->tasks[$agentId]
            ?? throw new InvalidArgumentException("Unknown campaign task [{$agentId}].");
    }

    public function transition(string $agentId, TaskStatus $status): void
    {
        $this->statusOf($agentId);
        $this->tasks[$agentId] = $status;
    }

    /** @return array<string, TaskStatus> */
    public function tasks(): array
    {
        return $this->tasks;
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return array_map(
            static fn (TaskStatus $status): string => $status->value,
            $this->tasks,
        );
    }
}
