<?php
namespace App\Assessor;

use DomainException;
use Illuminate\Support\Facades\DB;

final class CabinetWorkflow
{
    private function requireMember(int $cabinetId, int $actorId): void
    {
        if (!DB::table('assessor_cabinet_members')->where('cabinet_id', $cabinetId)
            ->where('user_id', $actorId)->exists()) {
            throw new DomainException('cabinet_access_denied');
        }
    }

    public function createCabinet(int $companyId, int $ownerId, string $name, string $mandateType): int
    {
        if (trim($name) === '' || !in_array($mandateType, ['vereador', 'deputado_estadual', 'deputado_federal'], true)) {
            throw new DomainException('invalid_cabinet');
        }
        return DB::transaction(function () use ($companyId, $ownerId, $name, $mandateType) {
            $cabinetId = DB::table('assessor_cabinets')->insertGetId([
                'company_id' => $companyId, 'name' => trim($name), 'mandate_type' => $mandateType,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('assessor_cabinet_members')->insert([
                'cabinet_id' => $cabinetId, 'user_id' => $ownerId, 'role' => 'admin',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            return $cabinetId;
        });
    }

    public function addAssessor(int $cabinetId, int $actorId, int $userId): void
    {
        $this->requireMember($cabinetId, $actorId);
        if (!DB::table('assessor_cabinet_members')->where('cabinet_id', $cabinetId)
            ->where('user_id', $actorId)->where('role', 'admin')->exists()) {
            throw new DomainException('cabinet_admin_required');
        }
        DB::table('assessor_cabinet_members')->insert([
            'cabinet_id' => $cabinetId, 'user_id' => $userId, 'role' => 'assessor',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function createDemand(int $cabinetId, int $actorId, string $subject, string $description): int
    {
        $this->requireMember($cabinetId, $actorId);
        if (trim($subject) === '' || trim($description) === '') {
            throw new DomainException('invalid_demand');
        }
        return DB::table('assessor_demands')->insertGetId([
            'cabinet_id' => $cabinetId, 'created_by' => $actorId,
            'subject' => trim($subject), 'description' => trim($description),
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function assignDemand(int $cabinetId, int $actorId, int $demandId, int $assessorId): void
    {
        $this->requireMember($cabinetId, $actorId);
        $this->requireMember($cabinetId, $assessorId);
        $updated = DB::table('assessor_demands')->where('cabinet_id', $cabinetId)
            ->where('id', $demandId)->update(['assigned_to' => $assessorId,
                'status' => 'in_progress', 'updated_at' => now()]);
        if (!$updated) { throw new DomainException('demand_not_found'); }
    }

    public function listDemands(int $cabinetId, int $actorId): array
    {
        $this->requireMember($cabinetId, $actorId);
        return DB::table('assessor_demands')->where('cabinet_id', $cabinetId)
            ->orderByDesc('id')->get()->toArray();
    }
}
