<?php
namespace App\Http\Controllers;

use App\Assessor\CabinetEntitlements;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AssessorEntitlementsController extends Controller
{
    public function index(Request $request, int $cabinet, CabinetEntitlements $entitlements): JsonResponse
    {
        try {
            return response()->json(['data' => $entitlements->status($cabinet, (int) $request->user()->id)]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
