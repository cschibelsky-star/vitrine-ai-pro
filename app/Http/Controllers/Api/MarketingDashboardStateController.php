<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Marketing\Application\MarketingDashboardStateReader;
use App\Marketing\Domain\Agents\AgentRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MarketingDashboardStateController extends Controller
{
    public function __invoke(
        Request $request,
        MarketingDashboardStateReader $stateReader,
        AgentRegistry $registry,
    ): JsonResponse {
        $expectedToken = (string) config('centro_ia.internal_token', '');
        $receivedToken = (string) $request->bearerToken();

        if ($expectedToken === '' || $receivedToken === '' || ! hash_equals($expectedToken, $receivedToken)) {
            return response()->json([
                'ok' => false,
                'error' => 'unauthorized',
            ], 401);
        }

        $registry->assertValid();
        $gemini = (array) config('marketing_agents.gemini', []);

        return response()->json([
            'ok' => true,
            'source' => 'marketing-agents-core',
            'runtime' => [
                'approval_mode' => (string) config('marketing_agents.approval_mode', 'unknown'),
                'schema_version' => (string) config('marketing_agents.schema_version', 'unknown'),
                'gemini_configured' => filled($gemini['api_key'] ?? null),
                'strategy_enabled' => (bool) ($gemini['strategy_enabled'] ?? false),
                'model' => (string) ($gemini['model'] ?? 'not configured'),
            ],
            'agents' => $registry->all(),
            'pipeline' => [
                ['label' => 'Estratégia', 'agents' => ['product_market_strategist']],
                ['label' => 'Planejamento', 'agents' => ['campaign_planner']],
                ['label' => 'Copy', 'agents' => ['copy_content']],
                ['label' => 'Criação', 'agents' => ['creative_director', 'video_producer']],
                ['label' => 'Distribuição', 'agents' => ['social_distribution']],
                ['label' => 'QA', 'agents' => ['qa_brand_guardian']],
            ],
            'state' => $stateReader->latest(),
        ]);
    }
}
