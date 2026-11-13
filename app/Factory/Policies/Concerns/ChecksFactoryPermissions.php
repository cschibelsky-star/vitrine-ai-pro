<?php
declare(strict_types=1);

namespace App\Factory\Policies\Concerns;

/**
 * Factory permissions are denied unless the authenticated user explicitly
 * supports the permission contract and grants the requested permission.
 *
 * This does not establish tenant ownership of a particular record; callers
 * must still check ownership separately before using these policies for
 * multi-tenant access.
 */
trait ChecksFactoryPermissions
{
    protected function allowed($user, string $permission): bool
    {
        if (!is_object($user) || !method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return $user->hasPermissionTo($permission) === true;
        } catch (\Throwable) {
            // Missing permissions or malfunctioning authorization backends
            // must never elevate an unverified user's privileges.
            return false;
        }
    }
}
