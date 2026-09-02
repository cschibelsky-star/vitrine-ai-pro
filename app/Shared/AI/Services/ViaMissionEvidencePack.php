<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use Illuminate\Support\Str;

class ViaMissionEvidencePack
{
    public function build(string $domain, ?string $targetProjectId, string $mission, array $evidence): array
    {
        $missionId = (string) Str::uuid();
        $normalized = [
            'version' => 'evidence_pack_v1',
            'mission_id' => $missionId,
            'domain' => $domain,
            'target_project_id' => $targetProjectId,
            'mission' => $mission,
            'collected_at' => now()->toISOString(),
            'sources' => $evidence,
            'safety' => [
                'read_only' => true,
                'files_written' => 0,
                'commands_executed' => 0,
                'deploys' => 0,
                'destructive_actions' => 0,
            ],
        ];

        $canonical = json_encode(
            $normalized,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        ) ?: '{}';

        $normalized['sha256'] = hash('sha256', $canonical);

        return $normalized;
    }

    public function compact(array $pack): string
    {
        return json_encode(
            $pack,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
    }
}
