<?php
declare(strict_types=1);

namespace App\Http\Controllers\AssessorOnline;

use App\AssessorOnline\DemandIntakeService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class CitizenController extends Controller
{
    private function tenantId(Request $request): int
    {
        $memberships = DB::table('assessor_memberships')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('active', true)->whereIn('role', ['admin', 'assessor'])
            ->pluck('tenant_id');
        abort_unless($memberships->count() === 1, 403, 'Gabinete indisponível ou ambíguo.');
        return (int) $memberships->first();
    }

    public function index(Request $request): View
    {
        $tenantId = $this->tenantId($request);
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:160']]);
        $query = DB::table('assessor_citizens')->where('tenant_id', $tenantId)
            ->select('id', 'name', 'created_at');
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . addcslashes($filters['search'], '%_\\') . '%');
        }
        return view('assessor-online.citizens.index', [
            'citizens' => $query->orderBy('name')->orderBy('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $this->tenantId($request);
        return view('assessor-online.citizens.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = $this->tenantId($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);
        $id = DB::table('assessor_citizens')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => trim($data['name']),
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('assessor.citizens.show', ['citizenId' => $id])
            ->with('status', 'Cidadão cadastrado.');
    }

    public function show(Request $request, int $citizenId): View
    {
        $tenantId = $this->tenantId($request);
        $citizen = DB::table('assessor_citizens')->where('tenant_id', $tenantId)
            ->where('id', $citizenId)->first(['id', 'name', 'email', 'phone']);
        abort_unless($citizen !== null, 404);
        $cases = DB::table('assessor_cases')->where('tenant_id', $tenantId)
            ->where('citizen_id', $citizenId)->orderByDesc('created_at')
            ->select('id', 'protocol', 'subject', 'status')->paginate(10);
        return view('assessor-online.citizens.show', compact('citizen', 'cases'));
    }

    public function registerDemand(Request $request, int $citizenId, DemandIntakeService $intake): RedirectResponse
    {
        $tenantId = $this->tenantId($request);
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:20000'],
        ]);
        $case = $intake->register($tenantId, $citizenId, $data['subject'], $data['description']);
        return redirect()->route('assessor.demands.show', ['caseId' => $case['id']])
            ->with('status', 'Demanda registrada com protocolo.');
    }
}
