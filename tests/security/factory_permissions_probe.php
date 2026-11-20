<?php
declare(strict_types=1);

/**
 * Security regression probe for Factory policy fail-open.
 * Run: php tests/security/factory_permissions_probe.php
 * Exit 1 means a policy authorization gap was detected.
 * This script is standalone and does not mutate production data.
 */
require dirname(__DIR__, 2) . '/app/Factory/Policies/Concerns/ChecksFactoryPermissions.php';

final class FactoryPermissionProbe {
    use \App\Factory\Policies\Concerns\ChecksFactoryPermissions;
    public function check($user, string $permission): bool {
        return $this->allowed($user, $permission);
    }
}
final class UserWithoutPermissionApi {}
final class UserWithPermissionApi {
    public function __construct(private bool $granted) {}
    public function hasPermissionTo(string $permission): bool { return $this->granted; }
}

$policy = new FactoryPermissionProbe();
$cases = [
    'guest denied' => [$policy->check(null, 'factory.manage'), false],
    'explicit deny respected' => [$policy->check(new UserWithPermissionApi(false), 'factory.manage'), false],
    'explicit grant respected' => [$policy->check(new UserWithPermissionApi(true), 'factory.manage'), true],
    'missing permission API must deny' => [$policy->check(new UserWithoutPermissionApi(), 'factory.manage'), false],
];
$failures = 0;
foreach ($cases as $label => [$actual, $expected]) {
    $pass = $actual === $expected;
    echo ($pass ? 'PASS' : 'FAIL') . ': ' . $label . PHP_EOL;
    if (!$pass) $failures++;
}
if ($failures) {
    fwrite(STDERR, "Security regression detected: {$failures} failure(s). No changes were applied.\n");
    exit(1);
}
echo "All security checks passed.\n";
