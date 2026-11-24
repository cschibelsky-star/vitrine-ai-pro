<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Tasks;

use App\Marketing\Infrastructure\Persistence\Models\MarketingAuditEvent;
use App\Marketing\Infrastructure\Persistence\Models\MarketingCampaignTask;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class MarketingTaskViaGate
{
    public function submitForReview(MarketingCampaignTask $task): MarketingCampaignTask
    {
        return DB::transaction(function () use ($task): MarketingCampaignTask {
            $task = MarketingCampaignTask::query()->lockForUpdate()->findOrFail($task->getKey());
            $status = (string) ($task->via_status ?? 'not_submitted');

            if (! in_array($status, ['not_submitted', 'changes_requested'], true)) {
                throw new RuntimeException('A tarefa já está aguardando decisão da VIA ou já foi liberada.');
            }

            $task->update([
                'status' => TaskStatus::AwaitingViaReview->value,
                'via_status' => 'awaiting_review',
                'via_note' => null,
                'via_reviewed_by' => null,
                'via_reviewed_at' => null,
            ]);

            $this->audit($task, 'via_review_requested', [
                'agent_definition_id' => $task->agent_definition_id,
            ]);

            return $task->refresh();
        });
    }

    public function decide(
        MarketingCampaignTask $task,
        bool $approved,
        ?string $note,
        int $supervisorId,
    ): MarketingCampaignTask {
        return DB::transaction(function () use ($task, $approved, $note, $supervisorId): MarketingCampaignTask {
            $task = MarketingCampaignTask::query()->lockForUpdate()->findOrFail($task->getKey());

            if (($task->via_status ?? null) !== 'awaiting_review') {
                throw new RuntimeException('A tarefa não possui uma decisão VIA pendente.');
            }

            $task->update([
                'status' => $approved
                    ? TaskStatus::ReadyForHandoff->value
                    : TaskStatus::NeedsRevision->value,
                'via_status' => $approved ? 'approved' : 'changes_requested',
                'via_note' => $note,
                'via_reviewed_by' => $supervisorId,
                'via_reviewed_at' => now(),
            ]);

            $this->audit($task, $approved ? 'via_handoff_approved' : 'via_changes_requested', [
                'supervisor_id' => $supervisorId,
                'note' => $note,
                'agent_definition_id' => $task->agent_definition_id,
                'handoff_released' => $approved,
            ]);

            return $task->refresh();
        });
    }

    private function audit(MarketingCampaignTask $task, string $eventType, array $metadata): void
    {
        MarketingAuditEvent::query()->create([
            'company_id' => $task->company_id,
            'campaign_id' => $task->campaign_id,
            'actor_type' => 'via',
            'actor_id' => $metadata['supervisor_id'] ?? null,
            'event_type' => $eventType,
            'entity_type' => 'marketing_campaign_task',
            'entity_id' => $task->getKey(),
            'metadata' => $metadata + [
                'via_status' => $task->via_status,
                'task_status' => $task->status,
            ],
        ]);
    }
}
