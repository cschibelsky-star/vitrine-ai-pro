<?php
namespace App\Http\Controllers;

use App\Assessor\CabinetAiAssistant;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AssessorIntelligenceController extends Controller
{
    public function generate(Request $request, int $cabinet, CabinetAiAssistant $assistant): JsonResponse
    {
        try {
            return response()->json(['data' => $assistant->recommendations($cabinet, (int) $request->user()->id, true)])
                ->header('Cache-Control', 'no-store, private');
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 403)
                ->header('Cache-Control', 'no-store, private');
        }
    }

    public function index(Request $request, int $cabinet, CabinetAiAssistant $assistant): JsonResponse
    {
        try {
            return response()->json(['data' => $assistant->recommendations($cabinet, (int) $request->user()->id)])
                ->header('Cache-Control', 'no-store, private');
        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 403);
        }
    }
}
