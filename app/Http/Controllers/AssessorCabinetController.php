<?php
namespace App\Http\Controllers;

use App\Assessor\CabinetWorkflow;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AssessorCabinetController extends Controller
{
    private function execute(callable $operation): JsonResponse
    {
        try { return response()->json(['data' => $operation()]); }
        catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }

    public function createCabinet(Request $request, CabinetWorkflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required','integer','exists:companies,id'],
            'name' => ['required','string','max:160'],
            'mandate_type' => ['required','in:vereador,deputado_estadual,deputado_federal'],
        ]);
        if ((int) $request->user()->company_id !== (int) $data['company_id']) {
            return response()->json(['error' => 'company_access_denied'], 403);
        }
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'cabinet_admin_required'], 403);
        }
        return $this->execute(fn () => ['id' => $workflow->createCabinet(
            (int) $data['company_id'], (int) $request->user()->id, $data['name'], $data['mandate_type']
        )]);
    }

    public function addMember(Request $request, int $cabinet, CabinetWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['user_id' => ['required','integer','exists:users,id']]);
        return $this->execute(function () use ($workflow, $cabinet, $request, $data) {
            $workflow->addAssessor($cabinet, (int) $request->user()->id, (int) $data['user_id']);
            return ['added' => true];
        });
    }

    public function createDemand(Request $request, int $cabinet, CabinetWorkflow $workflow): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required','string','max:200'],
            'description' => ['required','string','max:10000'],
        ]);
        return $this->execute(fn () => ['id' => $workflow->createDemand(
            $cabinet, (int) $request->user()->id, $data['subject'], $data['description']
        )]);
    }

    public function assign(Request $request, int $cabinet, int $demand, CabinetWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['assessor_id' => ['required','integer','exists:users,id']]);
        return $this->execute(function () use ($workflow, $cabinet, $demand, $request, $data) {
            $workflow->assignDemand($cabinet, (int) $request->user()->id, $demand, (int) $data['assessor_id']);
            return ['assigned' => true];
        });
    }

    public function updateStatus(Request $request, int $cabinet, int $demand, CabinetWorkflow $workflow): JsonResponse
    {
        $data = $request->validate(['status' => ['required','in:open,in_progress,resolved']]);
        return $this->execute(function () use ($workflow, $cabinet, $demand, $request, $data) {
            $workflow->changeStatus($cabinet, (int) $request->user()->id, $demand, $data['status']);
            return ['updated' => true];
        });
    }

    public function index(Request $request, int $cabinet, CabinetWorkflow $workflow): JsonResponse
    {
        return $this->execute(fn () => $workflow->listDemands($cabinet, (int) $request->user()->id));
    }
}
