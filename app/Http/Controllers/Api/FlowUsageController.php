<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowQuota;
use App\Models\FlowUsageReservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FlowUsageController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'metric' => ['required', 'string', 'max:100'],
            'quantity' => ['nullable', 'numeric', 'min:0.000001'],
        ]);

        return response()->json($this->quotaSnapshot(
            (int) $payload['company_id'],
            $payload['metric'],
            (float) ($payload['quantity'] ?? 0),
        ));
    }

    public function reserve(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'execution_id' => ['nullable', 'string', 'max:255'],
            'metric' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'min:0.000001'],
            'estimated_cost' => ['nullable', 'numeric', 'min:0'],
            'provider' => ['nullable', 'string', 'max:100'],
            'ttl' => ['nullable', 'integer', 'between:60,86400'],
            'metadata' => ['nullable', 'array'],
        ]);

        $result = DB::transaction(function () use ($payload) {
            $quota = FlowQuota::query()
                ->where('company_id', $payload['company_id'])
                ->where('metric', $payload['metric'])
                ->where('active', true)
                ->lockForUpdate()
                ->first();

            $snapshot = $this->quotaSnapshot(
                (int) $payload['company_id'],
                $payload['metric'],
                (float) $payload['quantity'],
                $quota,
            );

            if (! $snapshot['allowed']) {
                return ['snapshot' => $snapshot, 'reservation' => null];
            }

            $reservation = FlowUsageReservation::create([
                'reservation_uuid' => (string) Str::uuid(),
                'company_id' => $payload['company_id'],
                'workflow_uuid' => $payload['workflow_uuid'] ?? null,
                'execution_id' => $payload['execution_id'] ?? null,
                'metric' => $payload['metric'],
                'quantity' => $payload['quantity'],
                'estimated_cost' => $payload['estimated_cost'] ?? 0,
                'provider' => $payload['provider'] ?? null,
                'status' => 'reserved',
                'expires_at' => now()->addSeconds((int) ($payload['ttl'] ?? 900)),
                'metadata' => $payload['metadata'] ?? null,
            ]);

            return ['snapshot' => $snapshot, 'reservation' => $reservation];
        });

        if (! $result['reservation']) {
            return response()->json(['ok' => false] + $result['snapshot'], 409);
        }

        return response()->json([
            'ok' => true,
            'reservation_uuid' => $result['reservation']->reservation_uuid,
            'expires_at' => $result['reservation']->expires_at?->toIso8601String(),
            'quota' => $result['snapshot'],
        ], 201);
    }

    public function commit(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'reservation_uuid' => ['required', 'uuid'],
            'actual_cost' => ['nullable', 'numeric', 'min:0'],
            'provider' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        $reservation = DB::transaction(function () use ($payload) {
            $reservation = FlowUsageReservation::query()
                ->where('reservation_uuid', $payload['reservation_uuid'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservation->status === 'committed') {
                return $reservation;
            }

            if ($reservation->status !== 'reserved' || ($reservation->expires_at && $reservation->expires_at->isPast())) {
                abort(409, 'Reserva expirada ou indisponível para commit.');
            }

            $reservation->update([
                'status' => 'committed',
                'actual_cost' => $payload['actual_cost'] ?? $reservation->estimated_cost,
                'provider' => $payload['provider'] ?? $reservation->provider,
                'metadata' => array_merge($reservation->metadata ?? [], $payload['metadata'] ?? []),
                'committed_at' => now(),
            ]);

            return $reservation->fresh();
        });

        return response()->json([
            'ok' => true,
            'reservation_uuid' => $reservation->reservation_uuid,
            'status' => $reservation->status,
            'committed_at' => $reservation->committed_at?->toIso8601String(),
        ]);
    }

    private function quotaSnapshot(int $companyId, string $metric, float $requested = 0, ?FlowQuota $quota = null): array
    {
        $quota ??= FlowQuota::query()
            ->where('company_id', $companyId)
            ->where('metric', $metric)
            ->where('active', true)
            ->first();

        if (! $quota) {
            return [
                'allowed' => true,
                'unlimited' => true,
                'metric' => $metric,
                'requested' => $requested,
            ];
        }

        [$start, $end] = $this->periodBounds($quota->period);

        $usageQuery = FlowUsageReservation::query()
            ->where('company_id', $companyId)
            ->where('metric', $metric)
            ->whereIn('status', ['reserved', 'committed'])
            ->where(function ($query) {
                $query->where('status', 'committed')
                    ->orWhere(function ($query) {
                        $query->where('status', 'reserved')
                            ->where(function ($query) {
                                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            });
                    });
            });

        if ($start && $end) {
            $usageQuery->whereBetween('created_at', [$start, $end]);
        }

        $used = (float) $usageQuery->sum('quantity');
        $limit = (float) $quota->limit_value;
        $remaining = max(0, $limit - $used);

        return [
            'allowed' => $requested <= $remaining,
            'unlimited' => false,
            'metric' => $metric,
            'period' => $quota->period,
            'limit' => $limit,
            'used_or_reserved' => $used,
            'remaining' => $remaining,
            'requested' => $requested,
        ];
    }

    private function periodBounds(string $period): array
    {
        $now = Carbon::now();

        return match ($period) {
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [null, null],
        };
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }
}
