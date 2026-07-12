<?php

namespace App\Services;

use App\Models\FlowSchedule;
use App\Models\FlowWorkflow;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FlowSchedulerService
{
    public function upsert(array $payload): FlowSchedule
    {
        $workflow = FlowWorkflow::query()
            ->where('uuid', $payload['workflow_uuid'])
            ->firstOrFail();

        $companyId = $payload['company_id'] ?? $workflow->company_id;

        if ($workflow->company_id !== null && (int) $workflow->company_id !== (int) $companyId) {
            throw new RuntimeException('Workflow não pertence à empresa informada.');
        }

        $uuid = $payload['uuid'] ?? (string) Str::uuid();

        return FlowSchedule::updateOrCreate(
            ['uuid' => $uuid],
            [
                'company_id' => $companyId,
                'flow_workflow_id' => $workflow->getKey(),
                'workflow_uuid' => $workflow->uuid,
                'name' => $payload['name'],
                'timezone' => $payload['timezone'] ?? config('app.timezone', 'America/Sao_Paulo'),
                'recurrence_type' => $payload['recurrence_type'] ?? 'once',
                'rrule' => $payload['rrule'] ?? null,
                'calendar' => $payload['calendar'] ?? null,
                'execution_window' => $payload['execution_window'] ?? null,
                'holidays' => $payload['holidays'] ?? null,
                'payload' => $payload['payload'] ?? [],
                'priority' => $payload['priority'] ?? $workflow->priority ?? 100,
                'starts_at' => $payload['starts_at'] ?? null,
                'ends_at' => $payload['ends_at'] ?? null,
                'next_run_at' => $payload['next_run_at'] ?? $payload['starts_at'] ?? null,
                'status' => $payload['status'] ?? 'active',
                'is_active' => $payload['is_active'] ?? true,
                'metadata' => $payload['metadata'] ?? null,
            ],
        );
    }

    public function dispatchDue(int $limit = 50): array
    {
        $now = now();
        $claimed = [];

        DB::transaction(function () use ($limit, $now, &$claimed): void {
            $schedules = FlowSchedule::query()
                ->where('is_active', true)
                ->where('status', 'active')
                ->whereNotNull('next_run_at')
                ->where('next_run_at', '<=', $now)
                ->where(function ($query) use ($now) {
                    $query->whereNull('locked_until')->orWhere('locked_until', '<', $now);
                })
                ->orderBy('priority')
                ->orderBy('next_run_at')
                ->lockForUpdate()
                ->limit($limit)
                ->get();

            foreach ($schedules as $schedule) {
                $schedule->update(['locked_until' => $now->copy()->addMinutes(5)]);
                $claimed[] = $schedule->fresh(['workflow']);
            }
        });

        $results = [];

        foreach ($claimed as $schedule) {
            try {
                $execution = app(FlowRuntimeService::class)->start(
                    $schedule->workflow,
                    $schedule->payload ?? [],
                    [
                        'company_id' => $schedule->company_id,
                        'priority' => $schedule->priority,
                        'metadata' => array_merge($schedule->metadata ?? [], [
                            'schedule_uuid' => $schedule->uuid,
                            'scheduled_for' => $schedule->next_run_at?->toIso8601String(),
                        ]),
                    ],
                );

                $schedule->update([
                    'last_run_at' => $now,
                    'next_run_at' => $this->nextRun($schedule, $now),
                    'locked_until' => null,
                    'status' => $schedule->recurrence_type === 'once' ? 'completed' : 'active',
                    'is_active' => $schedule->recurrence_type !== 'once',
                ]);

                $results[] = ['schedule_uuid' => $schedule->uuid, 'execution_uuid' => $execution->uuid, 'ok' => true];
            } catch (\Throwable $exception) {
                $schedule->update(['locked_until' => null]);
                $results[] = ['schedule_uuid' => $schedule->uuid, 'ok' => false, 'error' => $exception->getMessage()];
            }
        }

        return $results;
    }

    private function nextRun(FlowSchedule $schedule, Carbon $from): ?Carbon
    {
        return match ($schedule->recurrence_type) {
            'hourly' => $from->copy()->addHour(),
            'daily' => $from->copy()->addDay(),
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addMonth(),
            'once' => null,
            default => $schedule->next_run_at?->isAfter($from) ? $schedule->next_run_at : null,
        };
    }
}
