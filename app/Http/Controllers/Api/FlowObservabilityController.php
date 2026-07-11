<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowDlqEntry;
use App\Models\FlowTelemetry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FlowObservabilityController extends Controller
{
    public function telemetry(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'event_id' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'execution_id' => ['nullable', 'string', 'max:190'],
            'trace_id' => ['nullable', 'string', 'max:190'],
            'correlation_id' => ['nullable', 'string', 'max:190'],
            'provider' => ['nullable', 'string', 'max:100'],
            'queue' => ['nullable', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:60'],
            'step' => ['nullable', 'string', 'max:120'],
            'duration_ms' => ['nullable', 'integer', 'min:0'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'tokens' => ['nullable', 'integer', 'min:0'],
            'minutes' => ['nullable', 'numeric', 'min:0'],
            'storage_bytes' => ['nullable', 'integer', 'min:0'],
            'metrics' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $eventId = $payload['event_id'] ?? (string) Str::uuid();

        $record = FlowTelemetry::firstOrCreate(
            ['event_id' => $eventId],
            array_merge($payload, ['event_id' => $eventId])
        );

        return response()->json([
            'ok' => true,
            'created' => $record->wasRecentlyCreated,
            'event_id' => $record->event_id,
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    public function dlq(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'entry_uuid' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'execution_id' => ['nullable', 'string', 'max:190'],
            'trace_id' => ['nullable', 'string', 'max:190'],
            'correlation_id' => ['nullable', 'string', 'max:190'],
            'provider' => ['nullable', 'string', 'max:100'],
            'queue' => ['nullable', 'string', 'max:100'],
            'attempts' => ['nullable', 'integer', 'min:1'],
            'failure_code' => ['nullable', 'string', 'max:100'],
            'failure_reason' => ['required', 'string', 'max:5000'],
            'exception_class' => ['nullable', 'string', 'max:255'],
            'exception_message' => ['nullable', 'string'],
            'stack_trace' => ['nullable', 'string'],
            'payload' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'status' => ['nullable', 'string', 'max:40'],
            'reprocessable' => ['nullable', 'boolean'],
            'failed_at' => ['nullable', 'date'],
        ]);

        $entryUuid = $payload['entry_uuid'] ?? (string) Str::uuid();

        $record = FlowDlqEntry::firstOrCreate(
            ['entry_uuid' => $entryUuid],
            array_merge([
                'entry_uuid' => $entryUuid,
                'attempts' => 1,
                'status' => 'pending',
                'reprocessable' => true,
                'failed_at' => now(),
            ], $payload)
        );

        return response()->json([
            'ok' => true,
            'created' => $record->wasRecentlyCreated,
            'entry_uuid' => $record->entry_uuid,
            'status' => $record->status,
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }
}
