<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Application\GeminiStrategyAgent;
use App\Marketing\Application\SimulatedCampaignRunner;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiStrategyAgentTest extends TestCase
{
    public function test_gemini_returns_structured_validated_strategy_and_usage_metadata(): void
    {
        config([
            'marketing_agents.hub.url' => 'https://hub.example.test/v1/execute',
            'marketing_agents.hub.token' => 'test-hub-token',
            'marketing_agents.hub.project_id' => 'vitrine-marketing-agents-core',
            'marketing_agents.hub.capability' => 'marketing_generation',
            'marketing_agents.hub.timeout' => 30,
        ]);

        Http::fake([
            'hub.example.test/*' => Http::response([
                'ok' => true,
                'output_text' => json_encode($this->strategyPayload()),
                'model' => 'gemini-2.5-flash',
                'execution_id' => 'exec-test-001',
            ]),
        ]);

        $agent = app(GeminiStrategyAgent::class);
        $strategy = $agent->execute($this->campaign());

        $this->assertSame('STRATEGY-CAM-SOCIAL-001', $strategy['strategy_id']);
        $this->assertSame('completed', $strategy['status']);
        $this->assertSame('centro-ia', $agent->metadata()['provider']);
        $this->assertSame('gemini-2.5-flash', $agent->metadata()['model']);
        $this->assertSame('exec-test-001', $agent->metadata()['execution_id']);
        $this->assertFalse($agent->metadata()['fallback']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://hub.example.test/v1/execute'
                && $request->hasHeader('Authorization', 'Bearer test-hub-token')
                && $request->hasHeader('X-Vitrine-Project', 'vitrine-marketing-agents-core')
                && $request['project_id'] === 'vitrine-marketing-agents-core'
                && $request['capability'] === 'marketing_generation'
                && $request['input']['response_format'] === 'json'
                && $request['input']['temperature'] === 0.2;
        });
    }

    public function test_campaign_falls_back_safely_when_gemini_fails(): void
    {
        config([
            'marketing_agents.hub.strategy_enabled' => true,
            'marketing_agents.hub.url' => 'https://hub.example.test/v1/execute',
            'marketing_agents.hub.token' => 'test-hub-token',
            'marketing_agents.hub.project_id' => 'vitrine-marketing-agents-core',
            'marketing_agents.hub.capability' => 'marketing_generation',
        ]);

        Http::fake([
            'hub.example.test/*' => Http::response(
                ['error' => ['message' => 'temporary failure']],
                503,
            ),
        ]);

        $result = app(SimulatedCampaignRunner::class)->run($this->campaign());

        $metadata = $result['execution_metadata']['product_market_strategist'];
        $this->assertSame('simulated', $metadata['provider']);
        $this->assertTrue($metadata['fallback']);
        $this->assertSame('completed', $result['status']);
        $this->assertFalse($result['published']);
    }

    /** @return array<string, mixed> */
    private function strategyPayload(): array
    {
        return [
            'product_readiness' => ['ready' => true],
            'icp' => [['segment' => 'pequenas empresas']],
            'personas' => [],
            'pain_points' => ['falta de tempo'],
            'desired_outcomes' => ['presença consistente'],
            'value_proposition' => 'Social media com IA e supervisão.',
            'positioning' => 'Equipe inteligente para pequenos negócios.',
            'differentiators' => ['fluxo integrado'],
            'objections' => [],
            'core_message' => 'Sua marca sempre presente.',
            'campaign_concept' => 'Presença inteligente',
            'recommended_channels' => ['instagram'],
            'assumptions' => [],
            'evidence_refs' => ['campaign:known_facts'],
            'open_questions' => [],
        ];
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
            'known_facts' => ['Produto da Vitrine IA Pro'],
            'missing_information' => [],
            'restrictions' => ['Não inventar preços', 'Não publicar'],
        ];
    }
}
