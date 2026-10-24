<?php
namespace App\Assessor;

use DomainException;
use Illuminate\Support\Facades\DB;

final class CabinetOperations
{
    private function authorize(int $cabinet, int $actor): void
    {
        if (!DB::table('assessor_cabinet_members')->where('cabinet_id', $cabinet)
            ->where('user_id', $actor)->exists()) {
            throw new DomainException('cabinet_access_denied');
        }
    }

    public function addCitizen(int $cabinet, int $actor, array $data): int
    {
        $this->authorize($cabinet, $actor);
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new DomainException('citizen_name_required');
        }
        return DB::table('assessor_citizens')->insertGetId([
            'cabinet_id' => $cabinet, 'name' => trim($data['name']),
            'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function citizens(int $cabinet, int $actor): array
    {
        $this->authorize($cabinet, $actor);
        return DB::table('assessor_citizens')->where('cabinet_id', $cabinet)
            ->orderBy('name')->get()->toArray();
    }

    public function schedule(int $cabinet, int $actor, array $data): int
    {
        $this->authorize($cabinet, $actor);
        if (isset($data['citizen_id']) && !DB::table('assessor_citizens')
            ->where('cabinet_id', $cabinet)->where('id', $data['citizen_id'])->exists()) {
            throw new DomainException('citizen_not_in_cabinet');
        }
        if (isset($data['assessor_id']) && !DB::table('assessor_cabinet_members')
            ->where('cabinet_id', $cabinet)->where('user_id', $data['assessor_id'])->exists()) {
            throw new DomainException('assessor_not_in_cabinet');
        }
        if (strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
            throw new DomainException('invalid_appointment_time');
        }
        return DB::table('assessor_appointments')->insertGetId([
            'cabinet_id' => $cabinet, 'citizen_id' => $data['citizen_id'] ?? null,
            'assessor_id' => $data['assessor_id'] ?? null, 'title' => $data['title'],
            'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'],
            'status' => 'scheduled', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function appointments(int $cabinet, int $actor): array
    {
        $this->authorize($cabinet, $actor);
        return DB::table('assessor_appointments')->where('cabinet_id', $cabinet)
            ->orderBy('starts_at')->get()->toArray();
    }

    public function metrics(int $cabinet, int $actor): array
    {
        $this->authorize($cabinet, $actor);
        $demands = DB::table('assessor_demands')->where('cabinet_id', $cabinet);
        return [
            'citizens' => DB::table('assessor_citizens')->where('cabinet_id', $cabinet)->count(),
            'demands_total' => (clone $demands)->count(),
            'demands_open' => (clone $demands)->where('status', 'open')->count(),
            'demands_in_progress' => (clone $demands)->where('status', 'in_progress')->count(),
            'demands_resolved' => (clone $demands)->where('status', 'resolved')->count(),
            'appointments' => DB::table('assessor_appointments')->where('cabinet_id', $cabinet)->count(),
        ];
    }
}
