<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Domain\Agents\AgentRegistry;
use App\Marketing\Domain\Tasks\TaskStatus;
use LogicException;

final readonly class MarketingOrchestrator
{
    public function __construct(private AgentRegistry $registry)
    {
    }

    public function createCampaign(string $campaignId): CampaignWorkflowState
    {
        $this->registry->assertValid();

        $agents = array_keys(array_filter(
            $this->registry->all(),
            static fn (array $agent, string $agentId): bool =>
                $agentId !== 'marketing_director'
                && (bool) ($agent['enabled'] ?? false),
            ARRAY_FILTER_USE_BOTH,
        ));

        $state = new CampaignWorkflowState($campaignId, $agents);
        $this->releaseReadyTasks($state);

        return $state;
    }

    /**
     * Executes the workflow without external side effects.
     *
     * Agents released in the same wave are logically parallel. The simulation
     * completes each wave deterministically so it can be asserted in E2E tests.
     *
     * @return array{
     *     campaign_id: string,
     *     mode: 'simulation',
     *     waves: list<list<string>>,
     *     final_state: array<string, string>,
     *     publish_performed: false,
     *     spend_performed: false
     * }
     */
    public function simulateCampaign(string $campaignId): array
    {
        $state = $this->createCampaign($campaignId);
        $waves = [];

        while (($readyAgents = $this->readyAgents($state)) !== []) {
            $waves[] = $readyAgents;

            foreach ($readyAgents as $agentId) {
                $this->start($state, $agentId);
            }

            foreach ($readyAgents as $agentId) {
                $this->complete($state, $agentId);
            }
        }

        foreach ($state->tasks() as $agentId => $status) {
            if ($status !== TaskStatus::Completed) {
                throw new LogicException(
                    "Simulation stalled with task [{$agentId}] in status [{$status->value}].",
                );
            }
        }

        return [
            'campaign_id' => $campaignId,
            'mode' => 'simulation',
            'waves' => $waves,
            'final_state' => $state->toArray(),
            'publish_performed' => false,
            'spend_performed' => false,
        ];
    }

    /** @return list<string> */
    public function readyAgents(CampaignWorkflowState $state): array
    {
        return array_keys(array_filter(
            $state->tasks(),
            static fn (TaskStatus $status): bool => $status === TaskStatus::Ready,
        ));
    }

    public function start(CampaignWorkflowState $state, string $agentId): void
    {
        if ($state->statusOf($agentId) !== TaskStatus::Ready) {
            throw new LogicException("Task [{$agentId}] is not ready.");
        }

        $state->transition($agentId, TaskStatus::Running);
    }

    public function complete(CampaignWorkflowState $state, string $agentId): void
    {
        if ($state->statusOf($agentId) !== TaskStatus::Running) {
            throw new LogicException("Task [{$agentId}] is not running.");
        }

        $state->transition($agentId, TaskStatus::Completed);
        $this->releaseReadyTasks($state);
    }

    public function block(CampaignWorkflowState $state, string $agentId): void
    {
        if (! (bool) ($this->registry->get($agentId)['may_block_pipeline'] ?? false)) {
            throw new LogicException("Agent [{$agentId}] cannot block the pipeline.");
        }

        $state->transition($agentId, TaskStatus::Blocked);
    }

    public function requestRevision(
        CampaignWorkflowState $state,
        string $validatorAgentId,
        string $targetAgentId,
    ): void {
        if (! (bool) ($this->registry->get($validatorAgentId)['may_block_pipeline'] ?? false)) {
            throw new LogicException("Agent [{$validatorAgentId}] cannot request revisions.");
        }

        if ($state->statusOf($targetAgentId) !== TaskStatus::Completed) {
            throw new LogicException("Task [{$targetAgentId}] has no completed delivery to revise.");
        }

        $affected = $this->descendantsOf($targetAgentId);
        $state->transition($targetAgentId, TaskStatus::NeedsRevision);

        foreach ($affected as $agentId) {
            $state->transition($agentId, TaskStatus::Pending);
        }

        $this->releaseReadyTasks($state);
    }

    private function releaseReadyTasks(CampaignWorkflowState $state): void
    {
        foreach ($state->tasks() as $agentId => $status) {
            if (! in_array($status, [TaskStatus::Pending, TaskStatus::NeedsRevision], true)) {
                continue;
            }

            $dependenciesComplete = true;

            foreach ($this->registry->dependenciesOf($agentId) as $dependency) {
                if ($state->statusOf($dependency) !== TaskStatus::Completed) {
                    $dependenciesComplete = false;
                    break;
                }
            }

            if ($dependenciesComplete) {
                $state->transition($agentId, TaskStatus::Ready);
            }
        }
    }

    /** @return list<string> */
    private function descendantsOf(string $targetAgentId): array
    {
        $descendants = [];
        $queue = [$targetAgentId];

        while ($queue !== []) {
            $parent = array_shift($queue);

            foreach ($this->registry->all() as $agentId => $agent) {
                if (
                    ! in_array($parent, (array) ($agent['depends_on'] ?? []), true)
                    || in_array($agentId, $descendants, true)
                ) {
                    continue;
                }

                $descendants[] = $agentId;
                $queue[] = $agentId;
            }
        }

        return $descendants;
    }
}
