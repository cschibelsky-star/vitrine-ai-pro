<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Campaigns;

use App\Marketing\Domain\Approvals\ApprovalDecision;
use App\Marketing\Domain\Tasks\TaskStatus;
use InvalidArgumentException;

final class CampaignState
{
    /** @var array<string, array<string, mixed>> */
    private array $tasks = [];

    /** @var array<string, array<string, mixed>> */
    private array $approvals = [];

    /** @var list<array<string, mixed>> */
    private array $artifacts = [];

    /** @param list<string> $channels */
    public function __construct(
        private readonly string $campaignId,
        private readonly string $productId,
        private readonly string $objective,
        private readonly string $audience,
        private readonly array $channels,
        private CampaignStatus $status = CampaignStatus::Draft,
        private ?string $currentStage = null,
        private ?string $startedAt = null,
        private ?string $completedAt = null,
        private ?string $blockedReason = null,
        private array $metadata = [],
    ) {
        if ($campaignId === '') {
            throw new InvalidArgumentException('Campaign id is required.');
        }

        if ($objective === '') {
            throw new InvalidArgumentException('Campaign objective is required.');
        }
    }

    /** @param list<string> $dependsOn */
    public function registerTask(string $agentId, array $dependsOn = []): void
    {
        if ($agentId === '') {
            throw new InvalidArgumentException('Agent id is required.');
        }

        $this->tasks[$agentId] = [
            'agent_id' => $agentId,
            'status' => TaskStatus::Pending,
            'depends_on' => array_values(array_unique(array_map('strval', $dependsOn))),
            'started_at' => null,
            'completed_at' => null,
            'attempts' => 0,
            'last_error' => null,
            'output_ref' => null,
        ];
    }

    public function task(string $agentId): array
    {
        if (! isset($this->tasks[$agentId])) {
            throw new InvalidArgumentException("Campaign task [{$agentId}] is not registered.");
        }

        return $this->tasks[$agentId];
    }

    /** @return array<string, array<string, mixed>> */
    public function tasks(): array
    {
        return $this->tasks;
    }

    public function markTaskReady(string $agentId): void
    {
        $this->transitionTask($agentId, TaskStatus::Ready);
    }

    public function startTask(string $agentId, string $at): void
    {
        $task = $this->task($agentId);
        $task['status'] = TaskStatus::Running;
        $task['started_at'] = $at;
        $task['attempts'] = ((int) $task['attempts']) + 1;
        $task['last_error'] = null;
        $this->tasks[$agentId] = $task;

        if ($this->status !== CampaignStatus::Running) {
            $this->status = CampaignStatus::Running;
            $this->startedAt ??= $at;
        }
    }

    public function completeTask(string $agentId, string $at, ?string $outputRef = null): void
    {
        $task = $this->task($agentId);
        $task['status'] = TaskStatus::Completed;
        $task['completed_at'] = $at;
        $task['output_ref'] = $outputRef;
        $task['last_error'] = null;
        $this->tasks[$agentId] = $task;
    }

    public function failTask(string $agentId, string $error): void
    {
        $task = $this->task($agentId);
        $task['status'] = TaskStatus::Failed;
        $task['last_error'] = $error;
        $this->tasks[$agentId] = $task;
        $this->block("Task [{$agentId}] failed: {$error}");
    }

    public function requestRevision(string $agentId): void
    {
        $this->transitionTask($agentId, TaskStatus::NeedsRevision);
    }

    public function blockTask(string $agentId, string $reason): void
    {
        $task = $this->task($agentId);
        $task['status'] = TaskStatus::Blocked;
        $task['last_error'] = $reason;
        $this->tasks[$agentId] = $task;
        $this->block($reason);
    }

    public function allDependenciesCompleted(string $agentId): bool
    {
        foreach ($this->task($agentId)['depends_on'] as $dependency) {
            if (! isset($this->tasks[$dependency]) || $this->tasks[$dependency]['status'] !== TaskStatus::Completed) {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    public function readyTaskIds(): array
    {
        $ready = [];

        foreach ($this->tasks as $agentId => $task) {
            if ($task['status'] !== TaskStatus::Pending) {
                continue;
            }

            if ($this->allDependenciesCompleted($agentId)) {
                $ready[] = $agentId;
            }
        }

        return $ready;
    }

    public function setApproval(string $key, ApprovalDecision $decision, ?string $note = null): void
    {
        $this->approvals[$key] = [
            'decision' => $decision,
            'note' => $note,
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function approvals(): array
    {
        return $this->approvals;
    }

    /** @param array<string, mixed> $artifact */
    public function addArtifact(array $artifact): void
    {
        $this->artifacts[] = $artifact;
    }

    /** @return list<array<string, mixed>> */
    public function artifacts(): array
    {
        return $this->artifacts;
    }

    public function markReady(): void
    {
        $this->status = CampaignStatus::Ready;
        $this->blockedReason = null;
    }

    public function pause(): void
    {
        $this->status = CampaignStatus::Paused;
    }

    public function block(string $reason): void
    {
        $this->status = CampaignStatus::Blocked;
        $this->blockedReason = $reason;
    }

    public function complete(string $at): void
    {
        $this->status = CampaignStatus::Completed;
        $this->completedAt = $at;
        $this->blockedReason = null;
    }

    public function cancel(): void
    {
        $this->status = CampaignStatus::Cancelled;
    }

    public function setCurrentStage(?string $stage): void
    {
        $this->currentStage = $stage;
    }

    public function status(): CampaignStatus
    {
        return $this->status;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'campaign_id' => $this->campaignId,
            'product_id' => $this->productId,
            'objective' => $this->objective,
            'audience' => $this->audience,
            'channels' => $this->channels,
            'status' => $this->status->value,
            'current_stage' => $this->currentStage,
            'tasks' => array_map(static fn (array $task): array => [
                ...$task,
                'status' => $task['status']->value,
            ], $this->tasks),
            'approvals' => array_map(static fn (array $approval): array => [
                ...$approval,
                'decision' => $approval['decision']->value,
            ], $this->approvals),
            'artifacts' => $this->artifacts,
            'started_at' => $this->startedAt,
            'completed_at' => $this->completedAt,
            'blocked_reason' => $this->blockedReason,
            'metadata' => $this->metadata,
        ];
    }

    private function transitionTask(string $agentId, TaskStatus $status): void
    {
        $task = $this->task($agentId);
        $task['status'] = $status;
        $this->tasks[$agentId] = $task;
    }
}
