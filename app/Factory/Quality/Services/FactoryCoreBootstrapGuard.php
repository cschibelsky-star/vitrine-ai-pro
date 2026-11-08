<?php

declare(strict_types=1);

namespace App\Factory\Quality\Services;

use RuntimeException;

/**
 * Manual bootstrap authorization for non-production Factory Core environments.
 * Application flags alone never authorize a migration.
 */
final class FactoryCoreBootstrapGuard
{
    public function assertAllowed(
        bool $apply,
        string $confirmation,
        string $environment,
        bool $serverEnabled
    ): void {
        if (! $apply) {
            throw new RuntimeException('Factory Core bootstrap runs in plan-only mode by default.');
        }

        if (! $serverEnabled) {
            throw new RuntimeException('Factory Core bootstrap blocked: server-side opt-in is disabled.');
        }

        if (! in_array(strtolower(trim($environment)), [
            'local', 'testing', 'hml', 'homologation', 'staging',
        ], true)) {
            throw new RuntimeException('Factory Core bootstrap blocked outside approved non-production environments.');
        }

        if ($confirmation !== 'FACTORY_CORE_BOOTSTRAP') {
            throw new RuntimeException('Factory Core bootstrap blocked: explicit confirmation is required.');
        }
    }
}
