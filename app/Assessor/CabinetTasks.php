<?php
namespace App\Assessor;

use DomainException;
use Illuminate\Support\Facades\DB;

final class CabinetTasks
{
    private function authorize(int $cabinet, int $actor): void
    {
        if (!DB::table('assessor_cabinet_members')->where('cabinet_id', $cabinet)
            ->where('user_id', $actor)->exists()) {
            throw new DomainException('cabinet_access_denied');
        }
    }

    public function create(int $cabinet, int $actor, array $data): int
    {
        $this->authorize($cabinet, $actor);
        if (isset($data['assigned_to'])) {
            $this->authorize($cabinet, (int) $data['assigned_to']);
        }
        if (isset($data['demand_id']) && !DB::table('assessor_demands')
            ->where('cabinet_id', $cabinet)->where('id', $data['demand_id'])->exists()) {
            throw new DomainException('demand_not_in_cabinet');
        }
        return DB::table('assessor_tasks')->insertGetId([
            'cabinet_id' => $cabinet, 'created_by' => $actor,
            'assigned_to' => $data['assigned_to'] ?? null,
            'demand_id' => $data['demand_id'] ?? null,
            'title' => trim($data['title']),
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function all(int $cabinet, int $actor): array
    {
        $this->authorize($cabinet, $actor);
        return DB::table('assessor_tasks')->where('cabinet_id', $cabinet)
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')->orderByDesc('id')->get()->toArray();
    }

    public function updateStatus(int $cabinet, int $actor, int $task, string $status): void
    {
        $this->authorize($cabinet, $actor);
        if (!in_array($status, ['pending', 'in_progress', 'completed', 'cancelled'], true)) {
            throw new DomainException('invalid_task_status');
        }
        $row = DB::table('assessor_tasks')->where('cabinet_id', $cabinet)->where('id', $task)->first();
        if (!$row) {
            throw new DomainException('task_not_found');
        }
        $admin = DB::table('assessor_cabinet_members')->where('cabinet_id', $cabinet)
            ->where('user_id', $actor)->where('role', 'admin')->exists();
        if (!$admin && (int) $row->assigned_to !== $actor && (int) $row->created_by !== $actor) {
            throw new DomainException('task_access_denied');
        }
        DB::table('assessor_tasks')->where('cabinet_id', $cabinet)->where('id', $task)
            ->update(['status' => $status, 'updated_at' => now()]);
    }
}
