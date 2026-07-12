<?php

namespace App\Services;

use App\Models\FlowExecution;
use App\Models\FlowUsageReservation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FlowRuntimeFinalizerService
{
    public function finalize(FlowExecution $execution, string $eventType, array $data = []): FlowExecution
    {
        $eventType = strtoupper($eventType);

        if (! in_array($eventType, [
            'FLOW_COMPLETED',
            'FLOW_FINISHED',
            'FLOW_FAILED',
            'FLOW_TIMEOUT',
            'FLOW_CANCELLED',
        ], true)) {
            return $execution;
        }

        DB::transaction(function () use ($execution, $eventType, $data): void {
            $lockedExecution = FlowExecution::query()
                ->whereKey($execution->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $context = $lockedExecution->context ?? [];

            if (! empty($context['runtime_finalized_at'])) {
                return;
            }

            $reservation = null;

            if ($lockedExecution->usage_reservation_uuid) {
                $reservation = FlowUsageReservation::query()
                    ->where('reservation_uuid', $lockedExecution->usage_reservation_uuid)
                    ->lockForUpdate()
                    ->first();
            }

            if ($reservation && $reservation->status === 'reserved') {
                if (in_array($eventType, ['FLOW_COMPLETED', 'FLOW_FINISHED'], true)) {
                    $reservation->update([
                        'status' => 'committed',
                        'actual_cost' => $data['actual_cost'] ?? $reservation->estimated_cost,
                        'provider' => $data['provider'] ?? $lockedExecution->provider ?? $reservation->provider,
                        'metadata' => array_merge($reservation->metadata ?? [], [
                            'final_event' => $eventType,
                            'execution_uuid' => $lockedExecution->uuid,
                            'tokens' => $data['tokens'] ?? null,
                            'minutes' => $data['minutes'] ?? null,
                            'storage_bytes' => $data['storage_bytes'] ?? null,
                        ]),
                        'committed_at' => now(),
                    ]);
                } else {
                    $reservation->update([
                        'status' => 'released',
                        'metadata' => array_merge($reservation->metadata ?? [], [
                            'final_event' => $eventType,
                            'execution_uuid' => $lockedExecution->uuid,
                            'release_reason' => $data['failure_reason'] ?? $eventType,
                        ]),
                    ]);
                }
            }

            $context['runtime_finalized_at'] = now()->toIso8601String();
            $context['runtime_final_event'] = $eventType;
            $context['usage_final_status'] = $reservation?->status;
            $context['lock_released'] = true;

            $lockedExecution->update(['context' => $context]);
        });

        $fresh = $execution->fresh();
        $context = $fresh->context ?? [];
        $lockName = $context['runtime_lock_name'] ?? null;

        if ($lockName && $fresh->lock_owner) {
            Cache::restoreLock($lockName, $fresh->lock_owner)->release();
        }

        return $fresh->fresh();
    }
}
