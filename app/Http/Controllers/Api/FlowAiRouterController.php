<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FlowAiRouterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowAiRouterController extends Controller
{
    public function route(Request $request, FlowAiRouterService $router): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'execution_uuid' => ['nullable', 'uuid'],
            'trace_id' => ['nullable', 'uuid'],
            'correlation_id' => ['nullable', 'uuid'],
            'providers' => ['nullable', 'array'],
            'providers.*' => ['string', 'max:60'],
            'request' => ['required', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $result = $router->route(
            $payload['request'],
            [
                'providers' => $payload['providers'] ?? [],
                'company_id' => $payload['company_id'] ?? null,
                'workflow_uuid' => $payload['workflow_uuid'] ?? null,
                'execution_uuid' => $payload['execution_uuid'] ?? null,
                'trace_id' => $payload['trace_id'] ?? null,
                'correlation_id' => $payload['correlation_id'] ?? null,
                'metadata' => $payload['metadata'] ?? [],
            ],
        );

        return response()->json($result, $result['ok'] ? 200 : 503);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }
}
