<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Domain\Agents\AgentRegistry;
use App\Marketing\Domain\Campaigns\CampaignState;
use App\Marketing\Domain\Campaigns\CampaignStatus;
use App\Marketing\Domain\Tasks\TaskStatus;
use LogicException;

final readonly class MarketingOrchestrator
{
    private const SCHEMAS = [
        'product_market_strategist' => 'strategy-output',
        'campaign_planner' => 'campaign-plan',
        'copy_content' => 'content-package',
        'creative_director' => 'creative-package',
        'video_producer' => 'video-package',
        'social_distribution' => 'distribution-plan',
        'qa_brand_guardian' => 'qa-report',
    ];

    public function __construct(
        private AgentRegistry $registry,
        private ?DependencyEngine $dependencyEngine = null,
    ) {
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

    /** @param array<string, mixed> $campaign */
    public function createOperationalCampaign(array $campaign): CampaignState
    {
        $this->registry->assertValid();

        $state = new CampaignState(
            campaignId: (string) ($campaign['campaign_id'] ?? ''),
            productId: (string) ($campaign['product_id'] ?? $campaign['name'] ?? ''),
            objective: (string) ($campaign['objective'] ?? ''),
            audience: (string) ($campaign['audience'] ?? $campaign['target_audience'] ?? ''),
            channels: array_values(array_map('strval', (array) ($campaign['channels'] ?? []))),
            metadata: [
                'tenant_id' => $campaign['tenant_id'] ?? null,
                'company_id' => $campaign['company_id'] ?? null,
                'automation_mode' => $campaign['automation_mode'] ?? null,
            ],
        );

        $enabledAgentIds = array_keys(array_filter(
            $this->registry->all(),
            static fn (array $agent, string $agentId): bool =>
                $agentId !== 'marketing_director'
                && (bool) ($agent['enabled'] ?? false),
            ARRAY_FILTER_USE_BOTH,
        ));

        foreach ($enabledAgentIds as $agentId) {
            $dependencies = array_values(array_filter(
                $this->registry->dependenciesOf($agentId),
                static fn (string $dependency): bool => in_array($dependency, $enabledAgentIds, true),
            ));
            $state->registerTask($agentId, $dependencies);
        }

        $this->dependencies()->refresh($state);

        return $state;
    }

    /**
     * Runs the V1 workflow through the real executor abstraction while keeping
     * publication and spend disabled. The currently configured executor may
     * use live providers selectively and simulation fallback for the others.
     *
     * @param array<string, mixed> $campaign
     * @return array<string, mixed>
     */
    public function runOperationalCampaign(
        array $campaign,
        MarketingAgentExecutor $executor,
        SchemaContractValidator $validator,
    ): array {
        $validator->assertValid('campaign', $campaign);

        if (($campaign['automation_mode'] ?? null) !== 'assisted') {
            throw new LogicException('Marketing Orchestrator V1 requires assisted automation mode.');
        }

        $state = $this->createOperationalCampaign($campaign);
        $artifacts = [];
        $executionBatches = [];
        $executionMetadata = [];

        while (($readyAgents = $this->dependencies()->executableTaskIds($state)) !== []) {
            $executionBatches[] = $readyAgents;

            foreach ($readyAgents as $agentId) {
                $agent = $this->registry->get($agentId);

                if (($agent['may_publish'] ?? false) || ($agent['may_spend'] ?? false)) {
                    throw new LogicException("Agent [{$agentId}] has forbidden V1 permissions.");
                }

                $state->startTask($agentId, now()->toISOString());

                $dependencyIds = $this->registry->dependenciesOf($agentId);
                $inputs = $agentId === 'qa_brand_guardian'
                    ? $artifacts
                    : array_intersect_key($artifacts, array_flip($dependencyIds));

                $output = $executor->execute($agentId, $campaign, $inputs);
                $validator->assertValid(self::SCHEMAS[$agentId], $output);
                $metadata = $executor->metadataFor($agentId);
                $executionMetadata[$agentId] = $metadata;

                $artifactRef = "artifact:{$agentId}:v1";
                $artifacts[$agentId] = $output;
                $state->addArtifact([
                    'agent_id' => $agentId,
                    'schema' => self::SCHEMAS[$agentId],
                    'ref' => $artifactRef,
                    'metadata' => $metadata,
                    'content' => $output,
                ]);
                $state->completeTask($agentId, now()->toISOString(), $artifactRef);

                if (
                    $agentId === 'qa_brand_guardian'
                    && (string) ($output['result'] ?? '') !== 'approved'
                ) {
                    $state->blockTask($agentId, 'QA did not approve the campaign.');
                    break 2;
                }
            }

            $this->dependencies()->refresh($state);
        }

        if ($state->status() !== CampaignStatus::Blocked) {
            foreach ($state->tasks() as $agentId => $task) {
                if (($task['status'] ?? null) !== TaskStatus::Completed) {
                    throw new LogicException("Operational campaign stalled at [{$agentId}].");
                }
            }

            $state->complete(now()->toISOString());
        }

        return [
            'campaign_id' => $campaign['campaign_id'],
            'mode' => 'operational',
            'automation_mode' => 'assisted',
            'status' => $state->status()->value,
            'state' => $state->toArray(),
            'execution_batches' => $executionBatches,
            'execution_metadata' => $executionMetadata,
            'artifacts' => $artifacts,
            'qa_result' => $artifacts['qa_brand_guardian']['result'] ?? null,
            'published' => false,
            'spent' => false,
        ];
    }

    /**
     * Executes the legacy workflow without external side effects.
     *
     * @return array<string, mixed>
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

    private function dependencies(): DependencyEngine
    {
        return $this->dependencyEngine ?? app(DependencyEngine::class);
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
