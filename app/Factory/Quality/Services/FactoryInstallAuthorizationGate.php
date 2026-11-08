<?php

declare(strict_types=1);

namespace App\Factory\Quality\Services;

use RuntimeException;

/**
 * Temporary fail-closed guard until a trusted, independently verified
 * QA/VIA/human approval chain is implemented. Local JSON flags, CLI flags,
 * and build artifacts are not proof of authorization.
 */
final class FactoryInstallAuthorizationGate
{
    public function assertAllowed(bool $dryRun, array $context = []): void
    {
        if ($dryRun) {
            return;
        }

        // Deliberately refuse ALL real installations for the new FinalMaster
        // pipeline. Never treat caller-provided approvals as trusted.
        throw new RuntimeException(
            'Real Factory installation blocked: independent QA, VIA review and human homologation authorization are not implemented.'
        );
    }
}
