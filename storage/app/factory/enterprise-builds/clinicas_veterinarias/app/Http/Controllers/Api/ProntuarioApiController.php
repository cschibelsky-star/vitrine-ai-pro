<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProntuarioRequest;
use App\Http\Requests\UpdateProntuarioRequest;
use App\Models\Prontuario;
use App\Services\ProntuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProntuarioApiController extends Controller
{
    public function __construct(
        protected ProntuarioService $service,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(StoreProntuarioRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json($record, 201);
    }

    public function show(Prontuario $prontuario): JsonResponse
    {
        return response()->json($prontuario);
    }

    public function update(UpdateProntuarioRequest $request, Prontuario $prontuario): JsonResponse
    {
        return response()->json($this->service->update($prontuario, $request->validated()));
    }

    public function destroy(Prontuario $prontuario): JsonResponse
    {
        $this->service->delete($prontuario);

        return response()->json(['deleted' => true]);
    }
}
