<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowAuditLog;
use App\Models\FlowComplianceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FlowGovernanceController extends Controller
{
    public function audit(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'uuid' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'execution_uuid' => ['nullable', 'uuid'],
            'trace_id' => ['nullable', 'uuid'],
            'correlation_id' => ['nullable', 'uuid'],
            'event_type' => ['required', 'string', 'max:120'],
            'actor_type' => ['nullable', 'string', 'max:60'],
            'actor_id' => ['nullable', 'string', 'max:190'],
            'source' => ['nullable', 'string', 'max:120'],
            'context' => ['nullable', 'array'],
            'before' => ['nullable', 'array'],
            'after' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $uuid = $payload['uuid'] ?? (string) Str::uuid();

        $record = FlowAuditLog::firstOrCreate(
            ['uuid' => $uuid],
            [
                ...$payload,
                'uuid' => $uuid,
                'actor_type' => $payload['actor_type'] ?? 'system',
                'source' => $payload['source'] ?? 'vitrine-flow',
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'occurred_at' => $payload['occurred_at'] ?? now(),
            ],
        );

        return response()->json([
            'ok' => true,
            'created' => $record->wasRecentlyCreated,
            'audit_uuid' => $record->uuid,
            'recorded_at' => $record->created_at?->toIso8601String(),
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    public function createComplianceRequest(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'uuid' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'request_type' => ['required', 'in:export,access,rectification,anonymization,erasure,retention_review,consent_review'],
            'subject_type' => ['nullable', 'string', 'max:80'],
            'subject_reference' => ['nullable', 'string', 'max:190'],
            'legal_basis' => ['nullable', 'string', 'max:120'],
            'retention_days' => ['nullable', 'integer', 'between:0,3650'],
            'due_at' => ['nullable', 'date'],
            'requested_by' => ['nullable', 'string', 'max:190'],
            'reason' => ['nullable', 'string', 'max:4000'],
            'scope' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $uuid = $payload['uuid'] ?? (string) Str::uuid();

        $record = FlowComplianceRequest::firstOrCreate(
            ['uuid' => $uuid],
            [
                ...$payload,
                'uuid' => $uuid,
                'status' => 'pending',
                'due_at' => $payload['due_at'] ?? now()->addDays(15),
            ],
        );

        return response()->json([
            'ok' => true,
            'created' => $record->wasRecentlyCreated,
            'request' => $this->compliancePayload($record),
        ], $record->wasRecentlyCreated ? 201 : 200);
    }

    public function showComplianceRequest(Request $request, string $uuid): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $record = FlowComplianceRequest::query()->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'ok' => true,
            'request' => $this->compliancePayload($record),
        ]);
    }

    public function updateComplianceRequest(Request $request, string $uuid): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'status' => ['required', 'in:pending,in_review,approved,processing,completed,rejected,cancelled'],
            'processed_by' => ['nullable', 'string', 'max:190'],
            'result' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $record = FlowComplianceRequest::query()->where('uuid', $uuid)->firstOrFail();
        $record->fill($payload);

        if (in_array($payload['status'], ['completed', 'rejected', 'cancelled'], true)) {
            $record->processed_at = now();
        }

        $record->save();

        return response()->json([
            'ok' => true,
            'request' => $this->compliancePayload($record),
        ]);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }

    private function compliancePayload(FlowComplianceRequest $record): array
    {
        return [
            'uuid' => $record->uuid,
            'company_id' => $record->company_id,
            'request_type' => $record->request_type,
            'subject_type' => $record->subject_type,
            'subject_reference' => $record->subject_reference,
            'legal_basis' => $record->legal_basis,
            'status' => $record->status,
            'retention_days' => $record->retention_days,
            'due_at' => $record->due_at?->toIso8601String(),
            'processed_at' => $record->processed_at?->toIso8601String(),
            'requested_by' => $record->requested_by,
            'processed_by' => $record->processed_by,
            'reason' => $record->reason,
            'scope' => $record->scope,
            'result' => $record->result,
            'metadata' => $record->metadata,
        ];
    }
}
