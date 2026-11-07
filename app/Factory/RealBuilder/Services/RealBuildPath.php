<?php

declare(strict_types=1);

namespace App\Factory\RealBuilder\Services;

use InvalidArgumentException;

/**
 * Ensures generator and installer select the exact same run-scoped staging tree.
 * A missing runId retains the legacy CLI path for backwards compatibility.
 */
final class RealBuildPath
{
    public static function relative(string $blueprintSlug, ?string $runId): string
    {
        if (!preg_match('/^[a-z0-9][a-z0-9_-]*$/D', $blueprintSlug)) {
            throw new InvalidArgumentException('Invalid blueprint slug.');
        }
        if ($runId === null) {
            return $blueprintSlug;
        }
        if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/iD', $runId)) {
            throw new InvalidArgumentException('Invalid build run identifier.');
        }

        return $blueprintSlug . '/runs/' . strtolower($runId);
    }
}
