<?php
declare(strict_types=1);
namespace App\Factory\Policies\Concerns;
trait ChecksFactoryPermissions { protected function allowed($user,string $permission): bool { if (! $user) return false; if (method_exists($user,'hasPermissionTo')) return (bool)$user->hasPermissionTo($permission); return true; } }
