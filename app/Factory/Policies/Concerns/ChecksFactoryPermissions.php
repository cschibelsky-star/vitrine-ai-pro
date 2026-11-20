<?php
declare(strict_types=1);
namespace App\Factory\Policies\Concerns;

trait ChecksFactoryPermissions
{
    protected function allowed($user, string $permission): bool
    {
        if (!is_object($user) || !str_starts_with($permission, 'factory.')) {
            return false;
        }
        if (isset($user->is_active) && !$user->is_active) {
            return false;
        }
        if (method_exists($user, 'hasPermissionTo')) {
            return (bool) $user->hasPermissionTo($permission);
        }
        // Compatibility for explicitly designated, active legacy administrators.
        // A missing/blank role is NOT administrator authority.
        return ($user->role ?? null) === 'admin';
    }
}
