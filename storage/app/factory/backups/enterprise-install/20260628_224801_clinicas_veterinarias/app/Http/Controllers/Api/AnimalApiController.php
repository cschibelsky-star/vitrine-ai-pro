<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnimalRequest;
use App\Http\Requests\UpdateAnimalRequest;
use App\Models\Animal;
use App\Services\AnimalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnimalApiController extends Controller
{
    public function __construct(
        protected AnimalService $service,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->paginate((int) $request->integer('per_page', 15)));
    }

    public function store(StoreAnimalRequest $request): JsonResponse
    {
        $record = $this->service->create($request->validated());

        return response()->json($record, 201);
    }

    public function show(Animal $animal): JsonResponse
    {
        return response()->json($animal);
    }

    public function update(UpdateAnimalRequest $request, Animal $animal): JsonResponse
    {
        return response()->json($this->service->update($animal, $request->validated()));
    }

    public function destroy(Animal $animal): JsonResponse
    {
        $this->service->delete($animal);

        return response()->json(['deleted' => true]);
    }
}
