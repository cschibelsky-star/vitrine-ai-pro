<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowExecution;
use App\Models\FlowWorkflow;
use App\Services\FlowRuntimeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FlowRuntimeController extends Controller
{
    public function start(Request $request, FlowRuntimeService $runtime): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'workflow_uuid' => ['required', 'uuid', 'exists:flow_workflows,uuid'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'input' => ['nullable', 'array'],
            'trace_id' => ['nullable', 'uuid'],
            'correlation_id' => ['nullable', 'uuid'],
            'queue' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'integer', 'between:1,1000'],
            'provider' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        $workflow = FlowWorkflow::query()->where('uuid', $payload['workflow_uuid'])->firstOrFail();

        try {
            $execution = $runtime->start(
                $workflow,
                $payload['input'] ?? [],
                collect($payload)->except(['workflow_uuid', 'input'])->all(),
            );
        } catch (Throwable $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'execution' => $execution,
        ], 202);
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $execution = FlowExecution::query()
            ->with(['workflow:id,uuid,workflow_key,name,version', 'company:id,nome'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        return response()->json([
            'ok' => true,
            'execution' => $execution,
        ]);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }
}
