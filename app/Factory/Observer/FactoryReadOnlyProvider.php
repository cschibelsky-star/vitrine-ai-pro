<?php
declare(strict_types=1);

namespace App\Factory\Observer;

use App\Factory\Models\FactoryProject;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Read-only provider with explicit project scope.
 * Caller must derive authorized UUIDs from a trusted server-side tenant policy.
 * No tenant mapping exists in factory_projects; therefore default scope is empty.
 * Never expose this provider directly over HTTP.
 */
final class FactoryReadOnlyProvider
{
    /** @var list<string> */
    private array $authorizedProjectUuids;

    /** @param list<string> $authorizedProjectUuids Trusted server-side allowlist, never user input */
    public function __construct(array $authorizedProjectUuids = [])
    {
        foreach ($authorizedProjectUuids as $uuid) {
            if (!is_string($uuid) || !preg_match('/^[A-Za-z0-9_-]{1,80}$/D', $uuid)) {
                throw new RuntimeException('Invalid trusted project scope');
            }
        }
        $this->authorizedProjectUuids = array_values(array_unique($authorizedProjectUuids));
    }

    public function __invoke(string $capability, array $parameters): array
    {
        return match ($capability) {
            'factory.health' => $this->health(),
            'factory.project.inspect' => $this->project($parameters['project_id']),
            default => throw new RuntimeException('Capability not implemented by read-only provider'),
        };
    }

    private function health(): array
    {
        return [
            'application' => 'factory',
            'status' => 'configured',
            'mode' => 'read_only',
            'checks' => ['db_connectivity' => 'not_checked', 'qa' => 'not_checked'],
        ];
    }

    private function project(string $id): array
    {
        // Fail closed before any DB lookup when no trusted tenant/project scope exists.
        if ($this->authorizedProjectUuids === []) {
            throw new RuntimeException('Project scope not established');
        }
        if (!Schema::hasTable('factory_projects')) {
            throw new RuntimeException('Factory projects table is unavailable');
        }
        $project = FactoryProject::query()
            ->select(['id', 'uuid', 'slug', 'name', 'status', 'created_at', 'updated_at'])
            ->whereIn('uuid', $this->authorizedProjectUuids)
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('slug', $id);
                if (ctype_digit($id)) {
                    $query->orWhere('id', (int) $id);
                }
            })->first();
        if (!$project) {
            throw new RuntimeException('Project not found or not authorized');
        }
        return [
            'id' => (string) $project->id,
            'uuid' => (string) $project->uuid,
            'slug' => (string) $project->slug,
            'name' => (string) $project->name,
            'status' => (string) $project->status,
            'created_at' => $project->created_at?->toISOString(),
            'updated_at' => $project->updated_at?->toISOString(),
        ];
    }
}
