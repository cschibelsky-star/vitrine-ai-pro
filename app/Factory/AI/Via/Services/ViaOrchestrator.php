<?php

namespace App\Factory\AI\Via\Services;

use App\Services\FlowAiRouterService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ViaOrchestrator
{
    public function __construct(
        private readonly FlowAiRouterService $router,
    ) {
    }

    public function handle(array $request, array $context = []): array
    {
        $traceId = (string) ($context['trace_id'] ?? Str::uuid());
        $correlationId = (string) ($context['correlation_id'] ?? $traceId);

        $normalizedContext = [
            'company_id' => Arr::get($context, 'company_id'),
            'user_id' => Arr::get($context, 'user_id'),
            'product' => Arr::get($context, 'product', 'factory'),
            'session_id' => Arr::get($context, 'session_id'),
            'workflow_uuid' => Arr::get($context, 'workflow_uuid'),
            'execution_uuid' => Arr::get($context, 'execution_uuid'),
            'trace_id' => $traceId,
            'correlation_id' => $correlationId,
            'metadata' => Arr::get($context, 'metadata', []),
        ];

        $payload = array_merge($request, [
            'via_context' => $normalizedContext,
        ]);

        $result = $this->router->route($payload, [
            'providers' => Arr::get($context, 'providers', []),
            'company_id' => $normalizedContext['company_id'],
            'workflow_uuid' => $normalizedContext['workflow_uuid'],
            'execution_uuid' => $normalizedContext['execution_uuid'],
            'trace_id' => $traceId,
            'correlation_id' => $correlationId,
            'metadata' => $normalizedContext['metadata'],
        ]);

        return array_merge($result, [
            'via' => [
                'trace_id' => $traceId,
                'correlation_id' => $correlationId,
                'product' => $normalizedContext['product'],
                'session_id' => $normalizedContext['session_id'],
            ],
        ]);
    }
}
