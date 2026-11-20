<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/Factory/Observer/FactoryObserver.php';

use App\Factory\Observer\FactoryObserver;

$calls = [];
$observer = new FactoryObserver(function (string $capability, array $parameters) use (&$calls): array {
    $calls[] = [$capability, $parameters];
    return ['status' => 'fixture'];
});
$checks = 0;
$check = function (string $name, callable $test) use (&$checks): void {
    $test();
    $checks++;
    echo "PASS: {$name}\n";
};
$reject = function (callable $action): void {
    try { $action(); } catch (\InvalidArgumentException|\RuntimeException $e) { return; }
    throw new \RuntimeException('Expected rejection');
};
$check('health read', function () use ($observer): void {
    $r=$observer->inspect('factory.health');
    if ($r['mode']!=='READ_ONLY' || $r['verified_live']!==false) throw new \RuntimeException('Bad mode');
});
$check('project read', function () use ($observer): void { $observer->inspect('factory.project.inspect',['project_id'=>'p_123']); });
$check('build read', function () use ($observer): void { $observer->inspect('factory.build.inspect',['build_id'=>'b_123']); });
$check('QA read', function () use ($observer): void { $observer->inspect('factory.qa.inspect',['build_id'=>'b_123']); });
$check('deploy denied', function () use ($observer,$reject): void { $reject(fn()=> $observer->inspect('factory.deploy')); });
$check('write denied', function () use ($observer,$reject): void { $reject(fn()=> $observer->inspect('factory.project.update',['project_id'=>'p_123'])); });
$check('unknown parameter denied', function () use ($observer,$reject): void { $reject(fn()=> $observer->inspect('factory.health',['cmd'=>'ls'])); });
$check('path traversal denied', function () use ($observer,$reject): void { $reject(fn()=> $observer->inspect('factory.build.inspect',['build_id'=>'../secret'])); });
$check('missing ID denied', function () use ($observer,$reject): void { $reject(fn()=> $observer->inspect('factory.qa.inspect')); });
$check('invalid ID type denied', function () use ($observer,$reject): void { $reject(fn()=> $observer->inspect('factory.project.inspect',['project_id'=>42])); });
$check('only approved calls reached provider', function () use (&$calls): void { if (count($calls)!==4) throw new \RuntimeException('Provider reached by rejected requests'); });
echo "{$checks} observer checks passed\n";
