<?php
declare(strict_types=1);

namespace App\AssessorOnline;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DemandIntakeService
{
    /**
     * Intake is server-side only. tenantId must come from authenticated tenant
     * context, never directly from an untrusted citizen form.
     */
    public function register(int $tenantId, int $citizenId, string $subject, string $description, ?array $issueLocation = null): array
    {
        if ($tenantId <= 0 || $citizenId <= 0 || trim($subject) === '' || trim($description) === '') {
            throw ValidationException::withMessages(['case' => 'Dados da solicitação inválidos.']);
        }
        if (mb_strlen($subject) > 180 || mb_strlen($description) > 20000) {
            throw ValidationException::withMessages(['case' => 'Limite de caracteres excedido.']);
        }

        return DB::transaction(function () use ($tenantId, $citizenId, $subject, $description, $issueLocation) {
            $citizen = DB::table('assessor_citizens')
                ->where('tenant_id', $tenantId)
                ->where('id', $citizenId)
                ->first(['id']);
            if (!$citizen) {
                throw ValidationException::withMessages(['citizen' => 'Cidadão não encontrado neste gabinete.']);
            }

            $protocol = (string) Str::uuid();
            $now = now();
            $caseId = DB::table('assessor_cases')->insertGetId([
                'tenant_id' => $tenantId,
                'citizen_id' => $citizenId,
                'protocol' => $protocol,
                'subject' => trim($subject),
                'description' => trim($description),
                'issue_location' => $issueLocation ? json_encode($issueLocation, JSON_THROW_ON_ERROR) : null,
                'status' => 'received',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('assessor_case_events')->insert([
                'tenant_id' => $tenantId,
                'case_id' => $caseId,
                'event_type' => 'received',
                'to_status' => 'received',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            return ['id' => $caseId, 'protocol' => $protocol, 'status' => 'received'];
        });
    }
}
