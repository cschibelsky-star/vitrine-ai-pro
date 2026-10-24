<?php
namespace App\Http\Controllers;

use App\Assessor\CabinetAiAssistant;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AssessorIntelligenceController extends Controller
{
    public function index(Request $request, int $cabinet, CabinetAiAssistant $assistant): JsonResponse
    {
        try {
            return response()->json(['data' => $assistant->recommendations($cabinet, (int) $request->user()->id)]);
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
