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

    /** @return list<string> */
    public function readyAgents(CampaignState $state): array
    {
        return $this->dependencies()->executableTaskIds($state);
    }

    public function start(CampaignState $state, string $agentId): void
    {
        if (! $this->dependencies()->canStart($state, $agentId)) {
            throw new LogicException("Task [{$agentId}] is not ready.");
        }

        $state->startTask($agentId, now()->toISOString());
    }

    public function complete(CampaignState $state, string $agentId, ?string $outputRef = null): void
    {
        if ($state->taskStatus($agentId) !== TaskStatus::Running) {
            throw new LogicException("Task [{$agentId}] is not running.");
        }

        $state->completeTask($agentId, now()->toISOString(), $outputRef);
        $this->dependencies()->refresh($state);
    }

    public function block(CampaignState $state, string $agentId, string $reason = 'Pipeline blocked by validator.'): void
    {
        if (! (bool) ($this->registry->get($agentId)['may_block_pipeline'] ?? false)) {
            throw new LogicException("Agent [{$agentId}] cannot block the pipeline.");
        }

        $state->blockTask($agentId, $reason);
    }

    public function requestRevision(
        CampaignState $state,
        string $validatorAgentId,
        string $targetAgentId,
    ): void {
        if (! (bool) ($this->registry->get($validatorAgentId)['may_block_pipeline'] ?? false)) {
            throw new LogicException("Agent [{$validatorAgentId}] cannot request revisions.");
        }

        if ($state->taskStatus($targetAgentId) !== TaskStatus::Completed) {
            throw new LogicException("Task [{$targetAgentId}] has no completed delivery to revise.");
        }

        $state->requestRevision($targetAgentId);

        foreach ($this->descendantsOf($targetAgentId) as $agentId) {
            $state->resetTask($agentId);
        }

        $this->dependencies()->refresh($state);
    }

    /**
     * Runs the V1 workflow through the executor abstraction while keeping
     * publication and spend disabled.
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

        while (($readyAgents = $this->readyAgents($state)) !== []) {
            $executionBatches[] = $readyAgents;

            foreach ($readyAgents as $agentId) {
                $agent = $this->registry->get($agentId);

                if (($agent['may_publish'] ?? false) || ($agent['may_spend'] ?? false)) {
                    throw new LogicException("Agent [{$agentId}] has forbidden V1 permissions.");
                }

                $this->start($state, $agentId);

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
                $this->complete($state, $agentId, $artifactRef);

                if (
                    $agentId === 'qa_brand_guardian'
                    && (string) ($output['result'] ?? '') !== 'approved'
                ) {
                    $state->blockTask($agentId, 'QA did not approve the campaign.');
                    break 2;
                }
            }
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

    private function dependencies(): DependencyEngine
    {
        return $this->dependencyEngine ?? app(DependencyEngine::class);
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
