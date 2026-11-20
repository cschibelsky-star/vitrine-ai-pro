<?php
declare(strict_types=1);

namespace App\Factory\Observer;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Mandatory persistent audit sink. Requires migration before use.
 * No secrets, parameters, or response bodies are recorded.
 */
final class DatabaseMissionAudit
{
    public function __invoke(array $event): void
    {
        foreach (['event','mission_id','tenant_id','actor_id','capability'] as $field) {
            if (!isset($event[$field]) || !is_string($event[$field]) || $event[$field] === '') {
                throw new RuntimeException('Invalid audit event');
            }
        }
        $count = DB::table('factory_observer_audit_events')->insert([
            'event' => $event['event'],
            'mission_id' => $event['mission_id'],
            'tenant_id' => $event['tenant_id'],
            'actor_id' => $event['actor_id'],
            'capability' => $event['capability'],
            'created_at' => now(),
        ]);
        if ($count !== true) {
            throw new RuntimeException('Audit persistence failed');
        }
    }
}
