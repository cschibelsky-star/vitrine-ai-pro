<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVacinaRequest;
use App\Http\Requests\UpdateVacinaRequest;
use App\Models\Vacina;
use App\Services\VacinaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacinaApiController extends Controller
{
    public function __construct(
        protected VacinaService $service,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(StoreVacinaRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json($record, 201);
    }

    public function show(Vacina $vacina): JsonResponse
    {
        return response()->json($vacina);
    }

    public function update(UpdateVacinaRequest $request, Vacina $vacina): JsonResponse
    {
        return response()->json($this->service->update($vacina, $request->validated()));
    }

    public function destroy(Vacina $vacina): JsonResponse
    {
        $this->service->delete($vacina);

        return response()->json(['deleted' => true]);
    }
}
