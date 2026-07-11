<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowEvent;
use App\Models\FlowExecution;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FlowEventCallbackController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $expectedToken = (string) config('vitrine_flow.callback_token');
        $receivedToken = (string) $request->bearerToken();

        if ($expectedToken === '' || ! hash_equals($expectedToken, $receivedToken)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'event_id' => ['nullable', 'string', 'max:160'],
            'event_type' => ['required', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:80'],
            'workflow' => ['nullable', 'string', 'max:160'],
            'execution_id' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', 'string', 'max:60'],
            'progress' => ['nullable', 'integer', 'between:0,100'],
            'step' => ['nullable', 'string', 'max:160'],
            'message' => ['nullable', 'string', 'max:5000'],
            'occurred_at' => ['nullable', 'date'],
            'data' => ['nullable', 'array'],
        ]);

        $eventId = $payload['event_id'] ?? (string) Str::uuid();

        [$event, $execution] = DB::transaction(function () use ($eventId, $payload): array {
            $event = FlowEvent::updateOrCreate(
                ['event_id' => $eventId],
                [
                    'event_type' => $payload['event_type'],
                    'source' => $payload['source'] ?? 'vitrine-ia-flow',
                    'workflow' => $payload['workflow'] ?? null,
                    'execution_id' => $payload['execution_id'] ?? null,
                    'status' => $payload['status'] ?? 'received',
                    'progress' => $payload['progress'] ?? null,
                    'step' => $payload['step'] ?? null,
                    'message' => $payload['message'] ?? null,
                    'payload' => $payload['data'] ?? [],
                    'occurred_at' => $payload['occurred_at'] ?? now(),
                    'processed_at' => now(),
                ],
            );

            $execution = $this->projectExecutionState($payload);

            return [$event, $execution];
        });

        return response()->json([
            'ok' => true,
            'event_id' => $event->event_id,
            'execution_uuid' => $execution?->uuid,
            'execution_status' => $execution?->status,
            'stored_at' => $event->updated_at?->toIso8601String(),
        ]);
    }

    private function projectExecutionState(array $payload): ?FlowExecution
    {
        $executionId = $payload['execution_id'] ?? null;

        if (! $executionId) {
            return null;
        }

        $execution = FlowExecution::query()
            ->where('uuid', $executionId)
            ->lockForUpdate()
            ->first();

        if (! $execution) {
            return null;
        }

        $eventType = strtoupper((string) $payload['event_type']);
        $status = $payload['status'] ?? $this->statusFromEvent($eventType) ?? $execution->status;
        $data = $payload['data'] ?? [];
        $context = $execution->context ?? [];

        if (array_key_exists('progress', $payload)) {
            $context['progress'] = $payload['progress'];
        }

        if (! empty($payload['step'])) {
            $context['current_step'] = $payload['step'];
        }

        if (! empty($payload['message'])) {
            $context['last_message'] = $payload['message'];
        }

        $context['last_event_type'] = $eventType;
        $context['last_event_at'] = $payload['occurred_at'] ?? now()->toIso8601String();

        $updates = [
            'status' => $status,
            'context' => $context,
            'attempts' => max((int) $execution->attempts, (int) ($data['attempt'] ?? 1)),
        ];

        if (in_array($eventType, ['FLOW_STARTED', 'FLOW_STEP_STARTED'], true) && ! $execution->started_at) {
            $updates['started_at'] = $payload['occurred_at'] ?? now();
        }

        if (in_array($eventType, ['FLOW_COMPLETED', 'FLOW_FINISHED'], true)) {
            $updates['status'] = 'completed';
            $updates['output'] = $data['output'] ?? $data;
            $updates['finished_at'] = $payload['occurred_at'] ?? now();
            $updates['failure_reason'] = null;
        }

        if (in_array($eventType, ['FLOW_FAILED', 'FLOW_TIMEOUT', 'FLOW_CANCELLED'], true)) {
            $updates['finished_at'] = $payload['occurred_at'] ?? now();
            $updates['failure_reason'] = $payload['message'] ?? ($data['failure_reason'] ?? $eventType);
        }

        if ($eventType === 'FLOW_RETRY') {
            $updates['status'] = 'retrying';
            $updates['attempts'] = max((int) $execution->attempts + 1, (int) ($data['attempt'] ?? 0));
        }

        $execution->update($updates);

        return $execution->fresh();
    }

    private function statusFromEvent(string $eventType): ?string
    {
        return match ($eventType) {
            'FLOW_EXECUTION_ACCEPTED' => 'accepted',
            'FLOW_STARTED' => 'running',
            'FLOW_STEP_STARTED' => 'running',
            'FLOW_STEP_FINISHED' => 'running',
            'FLOW_RETRY' => 'retrying',
            'FLOW_COMPLETED', 'FLOW_FINISHED' => 'completed',
            'FLOW_FAILED' => 'failed',
            'FLOW_TIMEOUT' => 'timed_out',
            'FLOW_CANCELLED' => 'cancelled',
            default => null,
        };
    }
}
