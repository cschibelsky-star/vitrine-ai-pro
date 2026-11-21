<?php

/**
 * O QUE É: serviço do fluxo administrativo de atas.
 * COMO FUNCIONA: registra pedidos e aplica revisões com eventos auditáveis.
 * FUNÇÃO NO SISTEMA: concentra regras do processo fora das rotas HTTP.
 */

namespace App\Services\Office;

use App\Http\Requests\StoreAdministrativeMinutesRequest;
use App\Http\Requests\ReviewAdministrativeMinutesRequest;
use App\Http\Requests\StoreAdministrativeMinutesResultRequest;
use App\Models\OfficeMission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdministrativeMinutesService
{
    public function create(StoreAdministrativeMinutesRequest $request, int $userId): OfficeMission
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $userId): OfficeMission {
            $mission = OfficeMission::firstOrCreate(
                ['idempotency_key' => $data['idempotency_key']],
                [
                    'id' => (string) Str::uuid(),
                    'created_by' => $userId,
                    'domain' => 'ECO',
                    'title' => 'Ata: '.$data['meeting_title'],
                    'description' => $data['notes'],
                    'priority' => $data['priority'],
                    'status' => 'planned',
                    'metadata' => [
                        'department' => 'administrativo',
                        'workflow' => 'meeting_minutes',
                        'meeting_title' => $data['meeting_title'],
                        'decisions' => array_values($data['decisions'] ?? []),
                        'open_items' => array_values($data['open_items'] ?? []),
                        'review_status' => 'awaiting_manager_review',
                        'executor' => null,
                    ],
                ]
            );

            abort_unless((int) $mission->created_by === $userId, 409, 'Chave de idempotência já utilizada.');

            if ($mission->wasRecentlyCreated) {
                $mission->events()->create([
                    'actor_id' => $userId,
                    'event_type' => 'minutes_requested',
                    'from_status' => null,
                    'to_status' => 'planned',
                    'request_id' => $data['idempotency_key'],
                    'payload' => ['source' => 'administrative_department'],
                ]);
            }

            return $mission;
        });
    }

    public function review(OfficeMission $mission, ReviewAdministrativeMinutesRequest $request, int $userId): OfficeMission
    {
        $data = $request->validated();

        return DB::transaction(function () use ($mission, $data, $userId): OfficeMission {
            $mission = OfficeMission::query()->lockForUpdate()->findOrFail($mission->getKey());
            abort_unless(($mission->metadata['workflow'] ?? null) === 'meeting_minutes', 422, 'Missão não é uma ata administrativa.');
            abort_unless(in_array($mission->metadata['review_status'] ?? null, ['awaiting_manager_review', 'changes_requested'], true), 409, 'A ata já foi revisada.');

            $approved = $data['decision'] === 'approve';
            $fromStatus = $mission->status;
            $toStatus = $approved ? 'completed' : 'planned';
            $metadata = $mission->metadata;
            $metadata['review_status'] = $approved ? 'approved' : 'changes_requested';
            $metadata['review_note'] = $data['note'] ?? null;
            $metadata['reviewed_by'] = $userId;

            $mission->update(['status' => $toStatus, 'metadata' => $metadata]);
            $mission->events()->create([
                'actor_id' => $userId,
                'event_type' => $data['decision'] === 'approve' ? 'minutes_approved' : 'minutes_changes_requested',
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'request_id' => (string) Str::uuid(),
                'payload' => ['note' => $data['note'] ?? null],
            ]);

            return $mission->refresh();
        });
    }

    public function submitResult(OfficeMission $mission, StoreAdministrativeMinutesResultRequest $request, int $userId): OfficeMission
    {
        $data = $request->validated();

        return DB::transaction(function () use ($mission, $data, $userId): OfficeMission {
            $mission = OfficeMission::query()->lockForUpdate()->findOrFail($mission->getKey());
            abort_unless(($mission->metadata['workflow'] ?? null) === 'meeting_minutes', 422, 'Missão não é uma ata administrativa.');
            abort_unless(in_array($mission->metadata['review_status'] ?? null, ['awaiting_manager_review', 'changes_requested'], true), 409, 'A ata não aceita novo resultado neste estado.');

            $fromStatus = $mission->status;
            $metadata = $mission->metadata;
            $metadata['minutes_text'] = $data['minutes_text'];
            $metadata['unresolved_items'] = array_values($data['unresolved_items'] ?? []);
            $metadata['agent_name'] = $data['agent_name'];
            $metadata['result_status'] = 'submitted';
            $metadata['review_status'] = 'awaiting_manager_review';
            $metadata['result_submitted_by'] = $userId;

            $mission->update(['status' => 'planned', 'metadata' => $metadata]);
            $mission->events()->create([
                'actor_id' => $userId,
                'event_type' => 'minutes_result_submitted',
                'from_status' => $fromStatus,
                'to_status' => 'planned',
                'request_id' => (string) Str::uuid(),
                'payload' => ['agent_name' => $data['agent_name']],
            ]);

            return $mission->refresh();
        });
    }
}
