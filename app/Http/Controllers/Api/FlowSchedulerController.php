<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowSchedule;
use App\Services\FlowSchedulerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FlowSchedulerController extends Controller
{
    public function upsert(Request $request, FlowSchedulerService $scheduler): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'uuid' => ['nullable', 'uuid'],
            'workflow_uuid' => ['required', 'uuid', 'exists:flow_workflows,uuid'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:190'],
            'timezone' => ['nullable', 'timezone'],
            'recurrence_type' => ['nullable', 'in:once,hourly,daily,weekly,monthly,rrule'],
            'rrule' => ['nullable', 'string', 'max:500'],
            'calendar' => ['nullable', 'array'],
            'execution_window' => ['nullable', 'array'],
            'holidays' => ['nullable', 'array'],
            'payload' => ['nullable', 'array'],
            'priority' => ['nullable', 'integer', 'between:1,1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'next_run_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ]);

        try {
            $schedule = $scheduler->upsert($payload);
        } catch (Throwable $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'schedule' => $schedule,
        ], $schedule->wasRecentlyCreated ? 201 : 200);
    }

    public function due(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $limit = min(max((int) $request->integer('limit', 50), 1), 200);

        $schedules = FlowSchedule::query()
            ->with('workflow:id,uuid,workflow_key,name,version')
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->orderBy('priority')
            ->orderBy('next_run_at')
            ->limit($limit)
            ->get();

        return response()->json(['ok' => true, 'schedules' => $schedules]);
    }

    public function dispatchDue(Request $request, FlowSchedulerService $scheduler): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $limit = min(max((int) $request->integer('limit', 50), 1), 200);
        $results = $scheduler->dispatchDue($limit);

        return response()->json([
            'ok' => true,
            'processed' => count($results),
            'results' => $results,
        ]);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }
}
