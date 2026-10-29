<?php
declare(strict_types=1);

namespace App\Http\Controllers\AssessorOnline;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $memberships = DB::table('assessor_memberships')
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('active', true)->whereIn('role', ['admin', 'assessor'])
            ->pluck('tenant_id');
        abort_unless($memberships->count() === 1, 403, 'Gabinete indisponível ou ambíguo.');
        $tenantId = (int) $memberships->first();

        $cases = DB::table('assessor_cases')->where('tenant_id', $tenantId);
        $counts = (clone $cases)->select('status')
            ->selectRaw('COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $recent = (clone $cases)->select('id', 'protocol', 'subject', 'status', 'updated_at')
            ->orderByDesc('updated_at')->orderByDesc('id')->limit(8)->get();
        return view('assessor-online.dashboard', [
            'citizenCount' => DB::table('assessor_citizens')->where('tenant_id', $tenantId)->count(),
            'caseCount' => (clone $cases)->count(),
            'pendingCount' => (clone $cases)->whereNotIn('status', ['resolved', 'closed'])->count(),
            'resolvedCount' => (clone $cases)->whereIn('status', ['resolved', 'closed'])->count(),
            'counts' => $counts,
            'recent' => $recent,
        ]);
    }
}
