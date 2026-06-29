<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistroRequest;
use App\Http\Requests\UpdateRegistroRequest;
use App\Models\Registro;
use App\Services\RegistroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistroApiController extends Controller
{
    public function __construct(
        protected RegistroService $service,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(StoreRegistroRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json($record, 201);
    }

    public function show(Registro $registro): JsonResponse
    {
        return response()->json($registro);
    }

    public function update(UpdateRegistroRequest $request, Registro $registro): JsonResponse
    {
        return response()->json($this->service->update($registro, $request->validated()));
    }

    public function destroy(Registro $registro): JsonResponse
    {
        $this->service->delete($registro);

        return response()->json(['deleted' => true]);
    }
}
