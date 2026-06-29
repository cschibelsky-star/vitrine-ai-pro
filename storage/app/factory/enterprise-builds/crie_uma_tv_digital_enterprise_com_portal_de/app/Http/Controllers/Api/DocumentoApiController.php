<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentoRequest;
use App\Http\Requests\UpdateDocumentoRequest;
use App\Models\Documento;
use App\Services\DocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentoApiController extends Controller
{
    public function __construct(
        protected DocumentoService $service,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(StoreDocumentoRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json($record, 201);
    }

    public function show(Documento $documento): JsonResponse
    {
        return response()->json($documento);
    }

    public function update(UpdateDocumentoRequest $request, Documento $documento): JsonResponse
    {
        return response()->json($this->service->update($documento, $request->validated()));
    }

    public function destroy(Documento $documento): JsonResponse
    {
        $this->service->delete($documento);

        return response()->json(['deleted' => true]);
    }
}
