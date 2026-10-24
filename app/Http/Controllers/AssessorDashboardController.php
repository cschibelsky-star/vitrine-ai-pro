<?php
namespace App\Http\Controllers;

use App\Assessor\CabinetWorkflow;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AssessorDashboardController extends Controller
{
    public function index(Request $request, int $cabinet, CabinetWorkflow $workflow): View
    {
        $demands = $workflow->listDemands($cabinet, (int) $request->user()->id);
        $members = \Illuminate\Support\Facades\DB::table('assessor_cabinet_members')
            ->join('users', 'users.id', '=', 'assessor_cabinet_members.user_id')
            ->where('assessor_cabinet_members.cabinet_id', $cabinet)
            ->select('users.id', 'users.name', 'assessor_cabinet_members.role')->get();
        $cabinetName = \Illuminate\Support\Facades\DB::table('assessor_cabinets')->where('id', $cabinet)->value('name');
        return view('assessor.dashboard', compact('cabinet', 'demands', 'members', 'cabinetName'));
    }
}
