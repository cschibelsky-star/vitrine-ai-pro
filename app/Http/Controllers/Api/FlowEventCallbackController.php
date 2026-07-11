<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return response()->json([
            'ok' => true,
            'event_id' => $event->event_id,
            'stored_at' => $event->updated_at?->toIso8601String(),
        ]);
    }
}
