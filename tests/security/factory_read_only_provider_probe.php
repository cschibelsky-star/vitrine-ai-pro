<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/Factory/Observer/FactoryReadOnlyProvider.php';

use App\Factory\Observer\FactoryReadOnlyProvider;

$checks = 0;
function assertReject(callable $action): void {
    try { $action(); } catch (\RuntimeException $e) { return; }
    throw new \RuntimeException('Expected rejection');
}
function check(string $label, callable $test): void {
    global $checks;
    $test(); $checks++;
    echo "PASS: {$label}\n";
}
check('health without database', function (): void {
    $result = (new FactoryReadOnlyProvider())('factory.health', []);
    if ($result['mode'] !== 'read_only') throw new \RuntimeException('Bad mode');
});
check('project lookup denied by default', function (): void {
    assertReject(fn() => (new FactoryReadOnlyProvider())('factory.project.inspect', ['project_id'=>'example']));
});
check('unsupported build capability denied', function (): void {
    assertReject(fn() => (new FactoryReadOnlyProvider())('factory.build.inspect', ['build_id'=>'build_1']));
});
check('unsupported QA capability denied', function (): void {
    assertReject(fn() => (new FactoryReadOnlyProvider())('factory.qa.inspect', ['build_id'=>'build_1']));
});
check('invalid trusted scope denied', function (): void {
    assertReject(fn() => new FactoryReadOnlyProvider(['../other-tenant']));
});
echo "{$checks} provider checks passed\n";
