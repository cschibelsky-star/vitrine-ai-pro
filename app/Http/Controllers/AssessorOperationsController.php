<?php
namespace App\Http\Controllers;

use App\Assessor\CabinetOperations;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AssessorOperationsController extends Controller
{
    private function run(callable $callback): JsonResponse
    {
        try { return response()->json(['data' => $callback()]); }
        catch (DomainException $e) { return response()->json(['error' => $e->getMessage()], 403); }
    }

    public function citizens(Request $request, int $cabinet, CabinetOperations $operations): JsonResponse
    {
        return $this->run(fn () => $operations->citizens($cabinet, (int) $request->user()->id));
    }

    public function addCitizen(Request $request, int $cabinet, CabinetOperations $operations): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:180'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
        ]);
        return $this->run(fn () => ['id' => $operations->addCitizen($cabinet, (int) $request->user()->id, $data)]);
    }

    public function appointments(Request $request, int $cabinet, CabinetOperations $operations): JsonResponse
    {
        return $this->run(fn () => $operations->appointments($cabinet, (int) $request->user()->id));
    }

    public function schedule(Request $request, int $cabinet, CabinetOperations $operations): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'citizen_id' => ['nullable', 'integer'],
            'assessor_id' => ['nullable', 'integer'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);
        return $this->run(fn () => ['id' => $operations->schedule($cabinet, (int) $request->user()->id, $data)]);
    }

    public function metrics(Request $request, int $cabinet, CabinetOperations $operations): JsonResponse
    {
        return $this->run(fn () => $operations->metrics($cabinet, (int) $request->user()->id));
    }
}
