<?php
declare(strict_types=1);

namespace App\Factory\Observer;

use RuntimeException;

/**
 * Server-side authorization decision, not a bearer token or user-provided payload.
 * Must be constructed only after authentication, tenant membership and mission policy checks.
 */
final class AuthorizedMissionScope
{
    /** @param list<string> $projectUuids */
    public function __construct(
        public readonly string $missionId,
        public readonly string $tenantId,
        public readonly string $actorId,
        public readonly array $projectUuids,
        public readonly array $capabilities,
        public readonly bool $approved,
    ) {
        foreach ([$missionId, $tenantId, $actorId] as $value) {
            if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/D', $value)) {
                throw new RuntimeException('Invalid mission identity');
            }
        }
        foreach ($projectUuids as $id) {
            if (!is_string($id) || !preg_match('/^[A-Za-z0-9_-]{1,80}$/D', $id)) {
                throw new RuntimeException('Invalid project scope');
            }
        }
        foreach ($capabilities as $capability) {
            if (!is_string($capability) || !in_array($capability, ['factory.health', 'factory.project.inspect'], true)) {
                throw new RuntimeException('Unsupported capability in mission scope');
            }
        }
    }

    public function assertAllowed(string $capability): void
    {
        if (!$this->approved || !in_array($capability, $this->capabilities, true)) {
            throw new RuntimeException('Mission capability denied');
        }
        if ($capability === 'factory.project.inspect' && $this->projectUuids === []) {
            throw new RuntimeException('Mission project scope denied');
        }
    }
}
