<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FlowWorkflowRegistryController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $data = $request->validate([
            'uuid' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'workflow_key' => ['required', 'string', 'max:160'],
            'name' => ['required', 'string', 'max:190'],
            'version' => ['required', 'string', 'max:40'],
            'category' => ['nullable', 'string', 'max:80'],
            'owner' => ['nullable', 'string', 'max:120'],
            'queue' => ['nullable', 'string', 'max:120'],
            'priority' => ['nullable', 'integer', 'between:0,1000'],
            'sla_seconds' => ['nullable', 'integer', 'min:1'],
            'timeout_seconds' => ['nullable', 'integer', 'min:1'],
            'max_retries' => ['nullable', 'integer', 'between:0,100'],
            'retry_backoff_seconds' => ['nullable', 'integer', 'min:0'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'default_provider' => ['nullable', 'string', 'max:80'],
            'n8n_workflow_id' => ['nullable', 'string', 'max:160'],
            'compatibility' => ['nullable', 'array'],
            'feature_flags' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'documentation' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:40'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $uuid = $data['uuid'] ?? null;
        unset($data['uuid']);

        $workflow = $uuid
            ? FlowWorkflow::query()->firstOrNew(['uuid' => $uuid])
            : FlowWorkflow::query()->firstOrNew([
                'company_id' => $data['company_id'] ?? null,
                'workflow_key' => $data['workflow_key'],
                'version' => $data['version'],
            ]);

        if (! $workflow->exists && ! $workflow->uuid) {
            $workflow->uuid = (string) Str::uuid();
        }

        $workflow->fill($data);
        $workflow->save();

        return response()->json([
            'ok' => true,
            'workflow' => $workflow->fresh(),
        ], $workflow->wasRecentlyCreated ? 201 : 200);
    }

    public function resolve(Request $request, string $uuid): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $workflow = FlowWorkflow::query()
            ->with('company:id,nome')
            ->where('uuid', $uuid)
            ->firstOrFail();

        return response()->json([
            'ok' => true,
            'workflow' => $workflow,
        ]);
    }

    private function authorized(Request $request): bool
    {
        $expectedToken = (string) config('vitrine_flow.callback_token');
        $receivedToken = (string) $request->bearerToken();

        return $expectedToken !== '' && hash_equals($expectedToken, $receivedToken);
    }
}
