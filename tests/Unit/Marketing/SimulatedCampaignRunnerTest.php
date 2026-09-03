<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Application\SchemaContractValidator;
use App\Marketing\Application\SimulatedCampaignRunner;
use InvalidArgumentException;
use Tests\TestCase;

class SimulatedCampaignRunnerTest extends TestCase
{
    public function test_vitrine_social_media_campaign_runs_end_to_end(): void
    {
        $result = app(SimulatedCampaignRunner::class)->run($this->campaign());

        $this->assertSame('completed', $result['status']);
        $this->assertSame('approved', $result['qa_result']);
        $this->assertFalse($result['published']);
        $this->assertFalse($result['spent']);
        $this->assertCount(7, $result['artifact_versions']);

        foreach ($result['tasks'] as $status) {
            $this->assertSame('completed', $status);
        }
    }

    public function test_design_and_video_share_the_same_execution_batch(): void
    {
        $result = app(SimulatedCampaignRunner::class)->run($this->campaign());

        $this->assertContains(
            ['creative_director', 'video_producer'],
            $result['execution_batches'],
        );
        $this->assertSame(
            ['artifact:creative_director:v1', 'artifact:video_producer:v1'],
            $result['artifact_versions']['social_distribution']['input_refs'],
        );
    }

    public function test_each_artifact_is_versioned_and_checksummed(): void
    {
        $result = app(SimulatedCampaignRunner::class)->run($this->campaign());

        foreach ($result['artifact_versions'] as $agentId => $artifact) {
            $this->assertSame($agentId, $artifact['artifact_key']);
            $this->assertSame(1, $artifact['version']);
            $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $artifact['checksum']);
        }
    }

    public function test_envelopes_never_allow_publication_spending_or_invented_facts(): void
    {
        $result = app(SimulatedCampaignRunner::class)->run($this->campaign());

        foreach ($result['envelopes'] as $envelope) {
            $this->assertFalse($envelope['constraints']['may_publish']);
            $this->assertFalse($envelope['constraints']['may_spend']);
            $this->assertFalse($envelope['constraints']['may_invent_facts']);
        }
    }

    public function test_contract_validator_rejects_incomplete_strategy(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SchemaContractValidator::class)->assertValid(
            'strategy-output',
            ['strategy_id' => 'INVALID'],
        );
    }

    /** @return array<string, mixed> */
    private function campaign(): array
    {
        return [
            'campaign_id' => 'CAM-SOCIAL-001',
            'tenant_id' => 1,
            'company_id' => 1,
            'product_id' => 1,
            'name' => 'Lançamento Vitrine Social Mídia',
            'objective' => 'Gerar demonstrações comerciais qualificadas',
            'automation_mode' => 'assisted',
            'status' => 'ready',
            'known_facts' => [
                'Produto da Vitrine IA Pro',
                'Publicação depende de aprovação humana',
            ],
            'missing_information' => [],
            'restrictions' => [
                'Não publicar',
                'Não contratar mídia',
                'Não inventar preços',
            ],
        ];
    }
}
