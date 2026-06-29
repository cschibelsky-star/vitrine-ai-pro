<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAgendamentoRequest;
use App\Http\Requests\UpdateAgendamentoRequest;
use App\Models\Agendamento;
use App\Services\AgendamentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendamentoApiController extends Controller
{
    public function __construct(
        protected AgendamentoService $service,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(StoreAgendamentoRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json($record, 201);
    }

    public function show(Agendamento $agendamento): JsonResponse
    {
        return response()->json($agendamento);
    }

    public function update(UpdateAgendamentoRequest $request, Agendamento $agendamento): JsonResponse
    {
        return response()->json($this->service->update($agendamento, $request->validated()));
    }

    public function destroy(Agendamento $agendamento): JsonResponse
    {
        $this->service->delete($agendamento);

        return response()->json(['deleted' => true]);
    }
}
