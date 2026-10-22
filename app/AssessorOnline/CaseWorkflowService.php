<?php
declare(strict_types=1);

namespace App\AssessorOnline;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Internal service only. The caller must derive the actor from the authenticated
 * session, not from submitted form data. No public route is exposed here.
 */
final class CaseWorkflowService
{
    private const ALLOWED_NEXT = [
        'received' => ['triaged'],
        'triaged' => ['referred', 'in_progress'],
        'referred' => ['in_progress', 'resolved'],
        'in_progress' => ['referred', 'resolved'],
        'resolved' => ['closed', 'in_progress'],
        'closed' => [],
    ];

    public function transition(
        int $tenantId,
        int $caseId,
        int $actorUserId,
        string $nextStatus,
        ?string $note = null
    ): array {
        $note = trim($note ?? '');
        if ($tenantId <= 0 || $caseId <= 0 || $actorUserId <= 0 ||
            !array_key_exists($nextStatus, self::ALLOWED_NEXT)) {
            throw ValidationException::withMessages(['case' => 'Transição inválida.']);
        }
        if (mb_strlen($note) > 2000) {
            throw ValidationException::withMessages(['note' => 'Observação excede 2.000 caracteres.']);
        }
        if (in_array($nextStatus, ['resolved', 'closed'], true) && $note === '') {
            throw ValidationException::withMessages(['note' => 'Informe a justificativa da conclusão.']);
        }

        return DB::transaction(function () use ($tenantId, $caseId, $actorUserId, $nextStatus, $note) {
            $authorized = DB::table('assessor_memberships')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $actorUserId)
                ->where('active', true)
                ->whereIn('role', ['admin', 'assessor'])
                ->exists();

            if (!$authorized) {
                throw ValidationException::withMessages(['case' => 'Operação não autorizada para este gabinete.']);
            }

            $case = DB::table('assessor_cases')
                ->where('tenant_id', $tenantId)
                ->where('id', $caseId)
                ->lockForUpdate()
                ->first(['id', 'protocol', 'status']);

            if (!$case || !in_array($nextStatus, self::ALLOWED_NEXT[$case->status] ?? [], true)) {
                throw ValidationException::withMessages(['case' => 'Solicitação indisponível ou transição não permitida.']);
            }

            $now = now();
            $updated = DB::table('assessor_cases')
                ->where('tenant_id', $tenantId)
                ->where('id', $caseId)
                ->where('status', $case->status)
                ->update(['status' => $nextStatus, 'updated_at' => $now]);

            if ($updated !== 1) {
                throw ValidationException::withMessages(['case' => 'Solicitação alterada por outro usuário.']);
            }

            DB::table('assessor_case_events')->insert([
                'tenant_id' => $tenantId,
                'case_id' => $caseId,
                'actor_user_id' => $actorUserId,
                'event_type' => 'status_changed',
                'from_status' => $case->status,
                'to_status' => $nextStatus,
                'note' => $note !== '' ? $note : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return [
                'id' => $caseId,
                'protocol' => $case->protocol,
                'previous_status' => $case->status,
                'status' => $nextStatus,
            ];
        });
    }
}
