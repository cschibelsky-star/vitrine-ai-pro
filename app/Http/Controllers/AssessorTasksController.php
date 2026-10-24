<?php
namespace App\Http\Controllers;

use App\Assessor\CabinetTasks;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AssessorTasksController extends Controller
{
    private function run(callable $callback): JsonResponse
    {
        try { return response()->json(['data' => $callback()]); }
        catch (DomainException $e) { return response()->json(['error' => $e->getMessage()], 403); }
    }

    public function index(Request $request, int $cabinet, CabinetTasks $tasks): JsonResponse
    {
        return $this->run(fn () => $tasks->all($cabinet, (int) $request->user()->id));
    }

    public function store(Request $request, int $cabinet, CabinetTasks $tasks): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:10000'],
            'assigned_to' => ['nullable', 'integer'],
            'demand_id' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date'],
        ]);
        return $this->run(fn () => ['id' => $tasks->create($cabinet, (int) $request->user()->id, $data)]);
    }

    public function update(Request $request, int $cabinet, int $task, CabinetTasks $tasks): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,in_progress,completed,cancelled']]);
        return $this->run(function () use ($tasks, $cabinet, $task, $request, $data) {
            $tasks->updateStatus($cabinet, (int) $request->user()->id, $task, $data['status']);
            return ['updated' => true];
        });
    }
}
