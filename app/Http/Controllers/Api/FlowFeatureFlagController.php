<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowFeatureFlag;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FlowFeatureFlagController extends Controller
{
    public function upsert(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'uuid' => ['nullable', 'uuid'],
            'key' => ['required', 'string', 'max:120'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'enabled' => ['required', 'boolean'],
            'beta' => ['nullable', 'boolean'],
            'rollout_percentage' => ['nullable', 'integer', 'between:0,100'],
            'priority' => ['nullable', 'integer', 'between:1,100000'],
            'config' => ['nullable', 'array'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);

        $uuid = $payload['uuid'] ?? (string) Str::uuid();

        $flag = FlowFeatureFlag::updateOrCreate(
            ['uuid' => $uuid],
            [
                'key' => $payload['key'],
                'company_id' => $payload['company_id'] ?? null,
                'plan_id' => $payload['plan_id'] ?? null,
                'workflow_uuid' => $payload['workflow_uuid'] ?? null,
                'enabled' => $payload['enabled'],
                'beta' => $payload['beta'] ?? false,
                'rollout_percentage' => $payload['rollout_percentage'] ?? 100,
                'priority' => $payload['priority'] ?? 100,
                'config' => $payload['config'] ?? null,
                'starts_at' => $payload['starts_at'] ?? null,
                'ends_at' => $payload['ends_at'] ?? null,
                'status' => $payload['status'] ?? 'active',
            ],
        );

        return response()->json([
            'ok' => true,
            'feature_flag' => $flag,
        ], $flag->wasRecentlyCreated ? 201 : 200);
    }

    public function check(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'key' => ['required', 'string', 'max:120'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'subject' => ['nullable', 'string', 'max:190'],
        ]);

        $companyId = $payload['company_id'] ?? null;
        $planId = $payload['plan_id'] ?? $this->resolvePlanId($companyId);
        $workflowUuid = $payload['workflow_uuid'] ?? null;

        $flags = FlowFeatureFlag::query()
            ->where('key', $payload['key'])
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($query) use ($companyId) {
                $query->whereNull('company_id');
                if ($companyId !== null) {
                    $query->orWhere('company_id', $companyId);
                }
            })
            ->where(function ($query) use ($planId) {
                $query->whereNull('plan_id');
                if ($planId !== null) {
                    $query->orWhere('plan_id', $planId);
                }
            })
            ->where(function ($query) use ($workflowUuid) {
                $query->whereNull('workflow_uuid');
                if ($workflowUuid !== null) {
                    $query->orWhere('workflow_uuid', $workflowUuid);
                }
            })
            ->get()
            ->sortByDesc(fn (FlowFeatureFlag $flag) => $this->specificity($flag, $companyId, $planId, $workflowUuid) * 100000 + $flag->priority)
            ->values();

        $flag = $flags->first();

        if (! $flag) {
            return response()->json([
                'ok' => true,
                'enabled' => false,
                'reason' => 'feature_flag_not_found',
                'key' => $payload['key'],
            ]);
        }

        $subject = $payload['subject'] ?? implode(':', [
            (string) ($companyId ?? 'global'),
            (string) ($workflowUuid ?? 'global'),
            $payload['key'],
        ]);

        $rolloutEnabled = $this->inRollout($subject, $flag->rollout_percentage);
        $enabled = $flag->enabled && $rolloutEnabled;

        return response()->json([
            'ok' => true,
            'enabled' => $enabled,
            'reason' => $enabled ? 'enabled' : ($flag->enabled ? 'outside_rollout' : 'disabled'),
            'key' => $flag->key,
            'uuid' => $flag->uuid,
            'company_id' => $companyId,
            'plan_id' => $planId,
            'workflow_uuid' => $workflowUuid,
            'beta' => $flag->beta,
            'rollout_percentage' => $flag->rollout_percentage,
            'config' => $flag->config,
        ]);
    }

    private function resolvePlanId(?int $companyId): ?int
    {
        if ($companyId === null) {
            return null;
        }

        return License::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['Ativa', 'ativa', 'active'])
            ->latest('id')
            ->value('plan_id');
    }

    private function specificity(FlowFeatureFlag $flag, ?int $companyId, ?int $planId, ?string $workflowUuid): int
    {
        $score = 0;
        $score += $flag->company_id !== null && $flag->company_id === $companyId ? 4 : 0;
        $score += $flag->plan_id !== null && $flag->plan_id === $planId ? 2 : 0;
        $score += $flag->workflow_uuid !== null && $flag->workflow_uuid === $workflowUuid ? 1 : 0;

        return $score;
    }

    private function inRollout(string $subject, int $percentage): bool
    {
        if ($percentage >= 100) {
            return true;
        }

        if ($percentage <= 0) {
            return false;
        }

        $bucket = hexdec(substr(hash('sha256', $subject), 0, 8)) % 100;

        return $bucket < $percentage;
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }
}
