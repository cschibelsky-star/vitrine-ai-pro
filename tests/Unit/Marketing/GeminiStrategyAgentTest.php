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
            'marketing_agents.gemini.api_key' => 'test-key-not-secret',
            'marketing_agents.gemini.model' => 'gemini-2.5-flash',
            'marketing_agents.gemini.timeout' => 30,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode($this->strategyPayload())]]],
                ]],
                'usageMetadata' => [
                    'promptTokenCount' => 120,
                    'candidatesTokenCount' => 80,
                    'totalTokenCount' => 200,
                ],
            ]),
        ]);

        $agent = app(GeminiStrategyAgent::class);
        $strategy = $agent->execute($this->campaign());

        $this->assertSame('STRATEGY-CAM-SOCIAL-001', $strategy['strategy_id']);
        $this->assertSame('completed', $strategy['status']);
        $this->assertSame('gemini', $agent->metadata()['provider']);
        $this->assertSame(200, $agent->metadata()['total_tokens']);

        Http::assertSent(function ($request): bool {
            return $request->hasHeader('x-goog-api-key', 'test-key-not-secret')
                && ! str_contains($request->url(), 'test-key-not-secret')
                && $request['generationConfig']['responseMimeType'] === 'application/json'
                && isset($request['generationConfig']['responseSchema']);
        });
    }

    public function test_campaign_falls_back_safely_when_gemini_fails(): void
    {
        config([
            'marketing_agents.gemini.strategy_enabled' => true,
            'marketing_agents.gemini.api_key' => 'test-key-not-secret',
            'marketing_agents.gemini.model' => 'gemini-2.5-flash',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(
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
