<?php
namespace App\Http\Controllers;

use App\Assessor\CabinetWorkflow;
use App\Assessor\CabinetOperations;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AssessorDashboardController extends Controller
{
    public function index(Request $request, int $cabinet, CabinetWorkflow $workflow, CabinetOperations $operations): View
    {
        $demands = $workflow->listDemands($cabinet, (int) $request->user()->id);
        $members = \Illuminate\Support\Facades\DB::table('assessor_cabinet_members')
            ->join('users', 'users.id', '=', 'assessor_cabinet_members.user_id')
            ->where('assessor_cabinet_members.cabinet_id', $cabinet)
            ->select('users.id', 'users.name', 'assessor_cabinet_members.role')->get();
        $cabinetName = \Illuminate\Support\Facades\DB::table('assessor_cabinets')->where('id', $cabinet)->value('name');
        $citizens = $operations->citizens($cabinet, (int) $request->user()->id);
        $appointments = $operations->appointments($cabinet, (int) $request->user()->id);
        $metrics = $operations->metrics($cabinet, (int) $request->user()->id);
        return view('assessor.dashboard', compact('cabinet', 'demands', 'members', 'cabinetName', 'citizens', 'appointments', 'metrics'));
    }
}
