<?php
namespace App\Assessor;

use DateTimeImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final class CabinetEntitlements
{
    public function status(int $cabinet, int $actor): array
    {
        $member = DB::table('assessor_cabinet_members as members')
            ->join('assessor_cabinets as cabinets', 'cabinets.id', '=', 'members.cabinet_id')
            ->join('users', 'users.id', '=', 'members.user_id')
            ->where('members.cabinet_id', $cabinet)->where('members.user_id', $actor)
            ->whereColumn('users.company_id', 'cabinets.company_id')
            ->whereIn('members.role', ['admin', 'assessor'])->exists();
        if (!$member) {
            throw new DomainException('cabinet_access_denied');
        }
        $companyId = DB::table('assessor_cabinets')->where('id', $cabinet)->value('company_id');
        if ($companyId === null) {
            throw new DomainException('cabinet_not_found');
        }
        $trial = TrialRequest::where('company_id', $companyId)->orderByDesc('created_at')->first();
        if (!$trial) {
            return [
                'mode' => 'unconfigured', 'access' => 'not_verified',
                'trial_days' => 15, 'trial_status' => null,
                'trial_ends_at' => null, 'commercial_plan' => null,
            ];
        }
        $end = $trial->ends_at;
        $now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $active = TrialPolicy::accessible($trial->status, $end, $now);
        return [
            'mode' => 'trial', 'access' => $active ? 'active' : 'not_verified',
            'trial_days' => 15, 'trial_status' => $trial->status,
            'trial_ends_at' => $end?->format(DATE_ATOM),
            'commercial_plan' => null,
        ];
    }
}
