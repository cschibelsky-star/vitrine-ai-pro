<?php

declare(strict_types=1);

namespace App\Factory\Domain\Agents;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Internal draft storage. Not an HTTP endpoint or runtime agent registry.
 * Caller must provide the authenticated server-side user, never a body-supplied ID.
 */
final class AgentDnaRepository
{
    public function create(User $actor, array $input): string
    {
        $this->authorize($actor);
        $dna = AgentDna::draft($input);
        $id = (string) Str::uuid();
        DB::transaction(function () use ($actor, $dna, $id): void {
            DB::table('factory_agent_dna_versions')->insert([
                'agent_id' => $id, 'version' => 1, 'created_by' => $actor->getKey(),
                'domain' => $dna['domain'], 'company_id' => $dna['company_id'],
                'dna' => json_encode($dna, JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        });
        return $id;
    }

    public function revise(User $actor, string $agentId, int $expectedVersion, array $input): int
    {
        $this->authorize($actor);
        $dna = AgentDna::draft($input);
        return DB::transaction(function () use ($actor, $agentId, $expectedVersion, $dna): int {
            // Lock the immutable root, serializing competing revisions.
            $root = DB::table('factory_agent_dna_versions')->where('agent_id', $agentId)
                ->where('version', 1)->lockForUpdate()->first();
            if (! $root || (int) $root->created_by !== (int) $actor->getKey()) {
                throw new RuntimeException('Draft not accessible.');
            }
            if ($root->domain !== $dna['domain']
                || ($root->company_id === null ? null : (int) $root->company_id) !== $dna['company_id']) {
                throw new RuntimeException('Domain/company is immutable.');
            }
            $latest = (int) DB::table('factory_agent_dna_versions')
                ->where('agent_id', $agentId)->max('version');
            if ($latest !== $expectedVersion) {
                throw new RuntimeException('Version conflict.');
            }
            $next = $latest + 1;
            DB::table('factory_agent_dna_versions')->insert([
                'agent_id' => $agentId, 'version' => $next, 'created_by' => $actor->getKey(),
                'domain' => $dna['domain'], 'company_id' => $dna['company_id'],
                'dna' => json_encode($dna, JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
            return $next;
        });
    }

    private function authorize(User $actor): void
    {
        if (! $actor->exists || ! $actor->getKey()
            || $actor->role !== 'admin' || $actor->is_active !== true) {
            throw new RuntimeException('Explicit active administrator required.');
        }
    }
}
