<?php
namespace App\Assessor;

use DomainException;
use Illuminate\Support\Facades\DB;

final class CabinetDemandNotes
{
    public function add(int $cabinet, int $actor, int $demand, string $note): int
    {
        if (!DB::table('assessor_cabinet_members')->where('cabinet_id', $cabinet)
            ->where('user_id', $actor)->exists()) {
            throw new DomainException('cabinet_access_denied');
        }
        if (!DB::table('assessor_demands')->where('cabinet_id', $cabinet)
            ->where('id', $demand)->exists()) {
            throw new DomainException('demand_not_found');
        }
        $note = trim($note);
        if ($note === '' || mb_strlen($note) > 500) {
            throw new DomainException('invalid_note');
        }
        return DB::table('assessor_demand_events')->insertGetId([
            'cabinet_id' => $cabinet, 'demand_id' => $demand,
            'actor_id' => $actor, 'event' => 'note_added', 'note' => $note,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
