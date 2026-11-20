<?php
declare(strict_types=1);

namespace App\Factory\Observer;

use App\Factory\Models\FactoryProject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Database-backed READ ONLY provider. No QA service calls: SmartQa2Service::inspect()
 * creates directories and therefore is NOT a safe Observer read operation.
 *
 * Not exposed as an HTTP endpoint; caller must authenticate and authorize
 * before invoking FactoryObserver::inspect().
 */
final class FactoryReadOnlyProvider
{
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
        // Avoid opening DB connections or mutating storage for a health probe.
        return [
            'application' => 'factory',
            'status' => 'configured',
            'database_driver' => (string) config('database.default'),
            'mode' => 'read_only',
            'checks' => ['db_connectivity' => 'not_checked', 'qa' => 'not_checked'],
        ];
    }

    private function project(string $id): array
    {
        if (!Schema::hasTable('factory_projects')) {
            throw new RuntimeException('Factory projects table is unavailable');
        }
        // Identifier can be a UUID, numeric primary key or slug. Only safe fields
        // are projected; never return metadata, secrets or arbitrary model arrays.
        $project = FactoryProject::query()
            ->select(['id', 'uuid', 'slug', 'name', 'status', 'created_at', 'updated_at'])
            ->where(function ($query) use ($id) {
                $query->where('uuid', $id)->orWhere('slug', $id);
                if (ctype_digit($id)) {
                    $query->orWhere('id', (int) $id);
                }
            })->first();
        if (!$project) {
            throw new RuntimeException('Project not found');
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
