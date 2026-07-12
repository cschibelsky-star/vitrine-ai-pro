<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlowDlqEntry;
use App\Models\FlowExecution;
use App\Models\FlowTelemetry;
use App\Models\FlowUsageReservation;
use App\Models\FlowWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class MissionControlController extends Controller
{
    public function overview(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $companyId = $request->integer('company_id') ?: null;
        $executionQuery = FlowExecution::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId));

        return response()->json([
            'ok' => true,
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'workflows_total' => FlowWorkflow::query()->when($companyId, fn ($query) => $query->where(function ($query) use ($companyId) {
                    $query->whereNull('company_id')->orWhere('company_id', $companyId);
                }))->count(),
                'workflows_active' => FlowWorkflow::query()->when($companyId, fn ($query) => $query->where(function ($query) use ($companyId) {
                    $query->whereNull('company_id')->orWhere('company_id', $companyId);
                }))->where('is_active', true)->where('status', 'active')->count(),
                'executions_running' => (clone $executionQuery)->whereIn('status', ['queued', 'dispatched', 'accepted', 'running', 'retrying'])->count(),
                'executions_completed_today' => (clone $executionQuery)->where('status', 'completed')->whereDate('finished_at', today())->count(),
                'executions_failed_today' => (clone $executionQuery)->whereIn('status', ['dispatch_failed', 'failed', 'timed_out', 'cancelled'])->whereDate('finished_at', today())->count(),
                'dlq_open' => Schema::hasTable('flow_dlq_entries')
                    ? FlowDlqEntry::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->whereIn('status', ['pending', 'open', 'failed'])->count()
                    : 0,
                'reserved_cost_month' => Schema::hasTable('flow_usage_reservations')
                    ? (float) FlowUsageReservation::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('estimated_cost')
                    : 0,
                'actual_cost_month' => Schema::hasTable('flow_usage_reservations')
                    ? (float) FlowUsageReservation::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->where('status', 'committed')->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('actual_cost')
                    : 0,
            ],
            'health' => $this->healthSnapshot(),
        ]);
    }

    public function executions(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $validated = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'status' => ['nullable', 'string', 'max:60'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'queue' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'between:1,200'],
        ]);

        $items = FlowExecution::query()
            ->with(['workflow:id,uuid,workflow_key,name,version', 'company:id,nome'])
            ->when($validated['company_id'] ?? null, fn ($query, $value) => $query->where('company_id', $value))
            ->when($validated['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($validated['workflow_uuid'] ?? null, fn ($query, $value) => $query->where('workflow_uuid', $value))
            ->when($validated['queue'] ?? null, fn ($query, $value) => $query->where('queue', $value))
            ->latest('id')
            ->limit((int) ($validated['limit'] ?? 50))
            ->get();

        return response()->json(['ok' => true, 'executions' => $items]);
    }

    public function workflows(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $companyId = $request->integer('company_id') ?: null;

        $items = FlowWorkflow::query()
            ->when($companyId, fn ($query) => $query->where(function ($query) use ($companyId) {
                $query->whereNull('company_id')->orWhere('company_id', $companyId);
            }))
            ->withCount('executions')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json(['ok' => true, 'workflows' => $items]);
    }

    public function queues(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $companyId = $request->integer('company_id') ?: null;

        $queues = FlowExecution::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->select('queue', 'status', DB::raw('COUNT(*) as total'))
            ->groupBy('queue', 'status')
            ->orderBy('queue')
            ->get()
            ->groupBy('queue')
            ->map(fn ($rows, $queue) => [
                'queue' => $queue,
                'statuses' => $rows->mapWithKeys(fn ($row) => [$row->status => (int) $row->total]),
                'total' => (int) $rows->sum('total'),
            ])->values();

        return response()->json(['ok' => true, 'queues' => $queues]);
    }

    public function costs(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $companyId = $request->integer('company_id') ?: null;
        $start = $request->date('start') ?? now()->startOfMonth();
        $end = $request->date('end') ?? now()->endOfMonth();

        $costs = FlowUsageReservation::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->whereBetween('created_at', [$start, $end])
            ->select('provider', 'metric', DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(estimated_cost) as estimated_cost'), DB::raw('SUM(COALESCE(actual_cost, 0)) as actual_cost'))
            ->groupBy('provider', 'metric')
            ->orderByDesc('actual_cost')
            ->get();

        return response()->json([
            'ok' => true,
            'period' => ['start' => $start->toIso8601String(), 'end' => $end->toIso8601String()],
            'costs' => $costs,
            'totals' => [
                'estimated_cost' => (float) $costs->sum('estimated_cost'),
                'actual_cost' => (float) $costs->sum('actual_cost'),
                'quantity' => (float) $costs->sum('quantity'),
            ],
        ]);
    }

    public function dlq(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $items = FlowDlqEntry::query()
            ->when($request->integer('company_id'), fn ($query, $value) => $query->where('company_id', $value))
            ->when($request->string('status')->toString(), fn ($query, $value) => $query->where('status', $value))
            ->latest('id')
            ->limit(min(max($request->integer('limit', 50), 1), 200))
            ->get();

        return response()->json(['ok' => true, 'dlq' => $items]);
    }

    public function health(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        return response()->json(['ok' => true, 'health' => $this->healthSnapshot()]);
    }

    private function healthSnapshot(): array
    {
        $database = false;
        $cache = false;
        $flow = false;

        try {
            DB::select('SELECT 1');
            $database = true;
        } catch (\Throwable) {
        }

        try {
            $key = 'mission-control-health-'.uniqid();
            Cache::put($key, 'ok', 10);
            $cache = Cache::get($key) === 'ok';
            Cache::forget($key);
        } catch (\Throwable) {
        }

        try {
            $response = Http::timeout(5)->get(rtrim((string) config('vitrine_flow.base_url'), '/'));
            $flow = $response->successful();
        } catch (\Throwable) {
        }

        return [
            'database' => $database ? 'healthy' : 'unavailable',
            'cache' => $cache ? 'healthy' : 'unavailable',
            'vitrine_ia_flow' => $flow ? 'healthy' : 'unavailable',
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }
}
