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
        $hub = (array) config('marketing_agents.hub', []);

        return response()->json([
            'ok' => true,
            'source' => 'marketing-agents-core',
            'runtime' => [
                'approval_mode' => (string) config('marketing_agents.approval_mode', 'unknown'),
                'schema_version' => (string) config('marketing_agents.schema_version', 'unknown'),
                'hub_configured' => filled($hub['url'] ?? null) && filled($hub['token'] ?? null),
                'strategy_enabled' => (bool) ($hub['strategy_enabled'] ?? false),
                'provider' => 'centro-ia',
                'capability' => (string) ($hub['capability'] ?? 'not configured'),
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
