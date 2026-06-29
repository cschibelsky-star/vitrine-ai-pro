<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\Categoria;
use App\Services\CategoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriaApiController extends Controller
{
    public function __construct(
        protected CategoriaService $service,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(StoreCategoriaRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json($record, 201);
    }

    public function show(Categoria $categoria): JsonResponse
    {
        return response()->json($categoria);
    }

    public function update(UpdateCategoriaRequest $request, Categoria $categoria): JsonResponse
    {
        return response()->json($this->service->update($categoria, $request->validated()));
    }

    public function destroy(Categoria $categoria): JsonResponse
    {
        $this->service->delete($categoria);

        return response()->json(['deleted' => true]);
    }
}
