<?php

namespace App\Http\Controllers\Api;

use App\Factory\AI\Via\Services\ViaOrchestrator;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlowAiRouterController extends Controller
{
    public function route(Request $request, ViaOrchestrator $via): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'user_id' => ['nullable', 'integer'],
            'product' => ['nullable', 'string', 'max:100'],
            'session_id' => ['nullable', 'string', 'max:120'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'execution_uuid' => ['nullable', 'uuid'],
            'trace_id' => ['nullable', 'uuid'],
            'correlation_id' => ['nullable', 'uuid'],
            'providers' => ['nullable', 'array'],
            'providers.*' => ['string', 'max:60'],
            'request' => ['required', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $result = $via->handle(
            $payload['request'],
            [
                'providers' => $payload['providers'] ?? [],
                'company_id' => $payload['company_id'] ?? null,
                'user_id' => $payload['user_id'] ?? null,
                'product' => $payload['product'] ?? 'factory',
                'session_id' => $payload['session_id'] ?? null,
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
