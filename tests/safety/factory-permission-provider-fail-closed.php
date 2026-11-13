<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2).'/app/Factory/Policies/Concerns/ChecksFactoryPermissions.php';

use App\Factory\Policies\Concerns\ChecksFactoryPermissions;

final class PermissionProbe {
    use ChecksFactoryPermissions;
    public function check($user, string $permission): bool {
        return $this->allowed($user, $permission);
    }
}

$probe = new PermissionProbe();
$cases = [
    [null, false],
    [new stdClass(), false],
    [new class { public function hasPermissionTo($permission): bool { return false; } }, false],
    [new class { public function hasPermissionTo($permission): bool { return true; } }, true],
    [new class { public function hasPermissionTo($permission): int { return 1; } }, false],
    [new class { public function hasPermissionTo($permission): bool { throw new RuntimeException('permission storage offline'); } }, false],
];
foreach ($cases as [$user,$expected]) {
    $actual=$probe->check($user,'factory.projects.view');
    if ($actual !== $expected) {
        fwrite(STDERR,"FAIL: permission contract returned unexpected authorization\n");
        exit(1);
    }
}
echo "PASS: null, missing permission provider, denial, explicit grant, non-bool, exception\n";
