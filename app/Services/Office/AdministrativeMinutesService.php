<?php

namespace App\Services\Office;

use App\Http\Requests\StoreAdministrativeMinutesRequest;
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
}
