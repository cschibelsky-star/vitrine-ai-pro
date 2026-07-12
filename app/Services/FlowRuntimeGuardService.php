<?php

namespace App\Services;

use App\Models\FlowFeatureFlag;
use App\Models\FlowQuota;
use App\Models\FlowUsageReservation;
use App\Models\FlowWorkflow;
use App\Models\License;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class FlowRuntimeGuardService
{
    public function prepare(FlowWorkflow $workflow, ?int $companyId, string $executionUuid, array $options): array
    {
        $feature = $this->checkFeature(
            $options['feature_key'] ?? null,
            $companyId,
            $workflow->uuid,
            $options['feature_subject'] ?? $executionUuid,
        );

        if ($feature && ! $feature['enabled']) {
            throw new RuntimeException('Feature indisponível para esta empresa, plano ou workflow.');
        }

        $lock = $this->acquireLock(
            $companyId,
            $workflow->uuid,
            $options['lock_ttl'] ?? $workflow->timeout_seconds ?? 300,
        );

        try {
            $reservation = $this->reserveUsage(
                $companyId,
                $workflow,
                $executionUuid,
                $options,
            );
        } catch (\Throwable $exception) {
            Cache::restoreLock($lock['name'], $lock['owner'])->release();
            throw $exception;
        }

        return [
            'feature' => $feature,
            'lock' => $lock,
            'reservation' => $reservation,
        ];
    }

    public function release(?string $lockName, ?string $owner): void
    {
        if ($lockName && $owner) {
            Cache::restoreLock($lockName, $owner)->release();
        }
    }

    private function checkFeature(?string $key, ?int $companyId, string $workflowUuid, string $subject): ?array
    {
        if (! $key) {
            return null;
        }

        $planId = $companyId ? License::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['Ativa', 'ativa', 'active'])
            ->latest('id')
            ->value('plan_id') : null;

        $flag = FlowFeatureFlag::query()
            ->where('key', $key)
            ->where('status', 'active')
            ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->where(fn ($query) => $query->whereNull('company_id')->when($companyId, fn ($query) => $query->orWhere('company_id', $companyId)))
            ->where(fn ($query) => $query->whereNull('plan_id')->when($planId, fn ($query) => $query->orWhere('plan_id', $planId)))
            ->where(fn ($query) => $query->whereNull('workflow_uuid')->orWhere('workflow_uuid', $workflowUuid))
            ->get()
            ->sortByDesc(function (FlowFeatureFlag $flag) use ($companyId, $planId, $workflowUuid): int {
                $specificity = 0;
                $specificity += $flag->company_id !== null && $flag->company_id === $companyId ? 4 : 0;
                $specificity += $flag->plan_id !== null && $flag->plan_id === $planId ? 2 : 0;
                $specificity += $flag->workflow_uuid !== null && $flag->workflow_uuid === $workflowUuid ? 1 : 0;
                return ($specificity * 100000) + $flag->priority;
            })
            ->first();

        if (! $flag) {
            return ['key' => $key, 'enabled' => false, 'reason' => 'feature_flag_not_found'];
        }

        $percentage = (int) $flag->rollout_percentage;
        $bucket = hexdec(substr(hash('sha256', $subject), 0, 8)) % 100;
        $rollout = $percentage >= 100 || ($percentage > 0 && $bucket < $percentage);

        return [
            'key' => $key,
            'uuid' => $flag->uuid,
            'enabled' => (bool) $flag->enabled && $rollout,
            'reason' => ! $flag->enabled ? 'disabled' : ($rollout ? 'enabled' : 'outside_rollout'),
            'config' => $flag->config,
        ];
    }

    private function acquireLock(?int $companyId, string $workflowUuid, int $ttl): array
    {
        $owner = (string) Str::uuid();
        $name = sprintf('vitrine-flow:runtime:company:%s:workflow:%s', $companyId ?? 'global', $workflowUuid);
        $ttl = max(1, min($ttl, 86400));

        if (! Cache::lock($name, $ttl, $owner)->get()) {
            throw new RuntimeException('Já existe uma execução concorrente deste workflow para a empresa.');
        }

        return ['name' => $name, 'owner' => $owner, 'ttl' => $ttl];
    }

    private function reserveUsage(?int $companyId, FlowWorkflow $workflow, string $executionUuid, array $options): ?FlowUsageReservation
    {
        $metric = $options['usage_metric'] ?? null;
        $quantity = (float) ($options['usage_quantity'] ?? 0);

        if (! $metric || $quantity <= 0) {
            return null;
        }

        if (! $companyId) {
            throw new RuntimeException('company_id é obrigatório para reservar consumo.');
        }

        return DB::transaction(function () use ($companyId, $workflow, $executionUuid, $options, $metric, $quantity) {
            $quota = FlowQuota::query()
                ->where('company_id', $companyId)
                ->where('metric', $metric)
                ->where('active', true)
                ->lockForUpdate()
                ->first();

            if ($quota) {
                [$start, $end] = $this->periodBounds($quota->period);
                $query = FlowUsageReservation::query()
                    ->where('company_id', $companyId)
                    ->where('metric', $metric)
                    ->whereIn('status', ['reserved', 'committed'])
                    ->where(function ($query) {
                        $query->where('status', 'committed')
                            ->orWhere(fn ($query) => $query->where('status', 'reserved')
                                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now())));
                    });

                if ($start && $end) {
                    $query->whereBetween('created_at', [$start, $end]);
                }

                $used = (float) $query->sum('quantity');
                if (($used + $quantity) > (float) $quota->limit_value) {
                    throw new RuntimeException('Cota insuficiente para iniciar a execução.');
                }
            }

            return FlowUsageReservation::create([
                'reservation_uuid' => (string) Str::uuid(),
                'company_id' => $companyId,
                'workflow_uuid' => $workflow->uuid,
                'execution_id' => $executionUuid,
                'metric' => $metric,
                'quantity' => $quantity,
                'estimated_cost' => $options['estimated_cost'] ?? $workflow->estimated_cost ?? 0,
                'provider' => $options['provider'] ?? $workflow->default_provider,
                'status' => 'reserved',
                'expires_at' => now()->addSeconds((int) ($options['usage_ttl'] ?? 900)),
                'metadata' => $options['metadata'] ?? null,
            ]);
        });
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
}
