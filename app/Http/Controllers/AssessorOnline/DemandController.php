<?php
declare(strict_types=1);

namespace App\Http\Controllers\AssessorOnline;

use App\AssessorOnline\CaseWorkflowService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DemandController extends Controller
{
    private function tenantId(Request $request): int
    {
        $userId = (int) $request->user()->getAuthIdentifier();
        $memberships = DB::table('assessor_memberships')
            ->where('user_id', $userId)->where('active', true)
            ->whereIn('role', ['admin', 'assessor'])->pluck('tenant_id');

        // Do not guess a tenant when a user belongs to multiple cabinets.
        abort_unless($memberships->count() === 1, 403, 'Gabinete indisponível ou ambíguo.');
        return (int) $memberships->first();
    }

    public function index(Request $request): View
    {
        $tenantId = $this->tenantId($request);
        $filters = $request->validate([
            'status' => ['nullable', 'in:received,triaged,referred,in_progress,resolved,closed'],
            'search' => ['nullable', 'string', 'max:180'],
        ]);
        $query = DB::table('assessor_cases as cases')
            ->join('assessor_citizens as citizens', function ($join) {
                $join->on('citizens.id', '=', 'cases.citizen_id')
                    ->on('citizens.tenant_id', '=', 'cases.tenant_id');
            })
            ->where('cases.tenant_id', $tenantId)
            ->select('cases.id', 'cases.protocol', 'cases.subject', 'cases.status', 'cases.updated_at', 'citizens.name as citizen_name');
        if (!empty($filters['status'])) {
            $query->where('cases.status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $term = '%' . addcslashes($filters['search'], '%_\\') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('cases.subject', 'like', $term)
                    ->orWhere('cases.protocol', 'like', $term);
            });
        }
        return view('assessor-online.demands.index', [
            'cases' => $query->orderByDesc('cases.updated_at')->orderByDesc('cases.id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, int $caseId): View
    {
        $tenantId = $this->tenantId($request);
        $case = DB::table('assessor_cases as cases')
            ->join('assessor_citizens as citizens', function ($join) {
                $join->on('citizens.id', '=', 'cases.citizen_id')
                    ->on('citizens.tenant_id', '=', 'cases.tenant_id');
            })
            ->where('cases.tenant_id', $tenantId)->where('cases.id', $caseId)
            ->select('cases.*', 'citizens.name as citizen_name')->first();
        abort_unless($case !== null, 404);
        $events = DB::table('assessor_case_events')->where('tenant_id', $tenantId)
            ->where('case_id', $caseId)->orderBy('created_at')->orderBy('id')->get();
        return view('assessor-online.demands.show', compact('case', 'events'));
    }

    public function transition(Request $request, int $caseId, CaseWorkflowService $workflow): RedirectResponse
    {
        $tenantId = $this->tenantId($request);
        $data = $request->validate([
            'status' => ['required', 'in:triaged,referred,in_progress,resolved,closed'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $workflow->transition($tenantId, $caseId, (int) $request->user()->getAuthIdentifier(), $data['status'], $data['note'] ?? null);
        return redirect()->route('assessor.demands.show', ['caseId' => $caseId])->with('status', 'Situação atualizada.');
    }
}
