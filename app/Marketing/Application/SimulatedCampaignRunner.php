<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use App\Marketing\Domain\Agents\AgentRegistry;
use App\Marketing\Domain\Contracts\CommonEnvelope;
use RuntimeException;

final readonly class SimulatedCampaignRunner
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
        private MarketingOrchestrator $orchestrator,
        private AgentRegistry $registry,
        private ResilientMarketingAgentExecutor $executor,
        private SchemaContractValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed> $campaign
     * @return array<string, mixed>
     */
    public function run(array $campaign): array
    {
        $this->validator->assertValid('campaign', $campaign);

        if (($campaign['automation_mode'] ?? null) !== 'assisted') {
            throw new RuntimeException('The V1 simulation requires assisted automation mode.');
        }

        $state = $this->orchestrator->createCampaign((string) $campaign['campaign_id']);
        $artifacts = [];
        $executionBatches = [];
        $envelopes = [];
        $executionMetadata = [];

        while (($readyAgents = $this->orchestrator->readyAgents($state)) !== []) {
            $executionBatches[] = $readyAgents;

            foreach ($readyAgents as $agentId) {
                $this->orchestrator->start($state, $agentId);

                $dependencyIds = $this->registry->dependenciesOf($agentId);
                $inputs = $agentId === 'qa_brand_guardian'
                    ? $artifacts
                    : array_intersect_key($artifacts, array_flip($dependencyIds));

                $envelope = new CommonEnvelope(
                    schemaVersion: '1.0.0',
                    companyId: (int) $campaign['company_id'],
                    campaignId: (string) $campaign['campaign_id'],
                    taskId: 'TASK-'.strtoupper(str_replace('_', '-', $agentId)),
                    agentId: $agentId,
                    attempt: 1,
                    language: 'pt-BR',
                    dependencies: $dependencyIds,
                    inputRefs: array_map(
                        static fn (string $dependency): string => "artifact:{$dependency}:v1",
                        array_keys($inputs),
                    ),
                    payload: ['campaign' => $campaign, 'inputs' => $inputs],
                );

                $output = $this->executor->execute($agentId, $campaign, $inputs);
                $executionMetadata[$agentId] = $this->executor->metadataFor($agentId);
                $this->validator->assertValid(self::SCHEMAS[$agentId], $output);

                $serialized = json_encode($output, JSON_THROW_ON_ERROR);
                $artifacts[$agentId] = $output;
                $envelopes[$agentId] = $envelope->toArray();
                $artifactVersions[$agentId] = [
                    'artifact_key' => $agentId,
                    'schema' => self::SCHEMAS[$agentId],
                    'version' => 1,
                    'checksum' => hash('sha256', $serialized),
                    'input_refs' => $envelope->toArray()['input_refs'],
                    'content' => $output,
                ];

                $this->orchestrator->complete($state, $agentId);
            }
        }

        $incomplete = array_filter(
            $state->toArray(),
            static fn (string $status): bool => $status !== 'completed',
        );

        if ($incomplete !== []) {
            throw new RuntimeException('Campaign simulation stopped with incomplete tasks.');
        }

        return [
            'campaign_id' => $campaign['campaign_id'],
            'product_name' => $campaign['name'],
            'mode' => 'simulation',
            'automation_mode' => 'assisted',
            'published' => false,
            'spent' => false,
            'tasks' => $state->toArray(),
            'execution_batches' => $executionBatches,
            'envelopes' => $envelopes,
            'execution_metadata' => $executionMetadata,
            'artifact_versions' => $artifactVersions ?? [],
            'qa_result' => $artifacts['qa_brand_guardian']['result'],
            'status' => 'completed',
        ];
    }
}
