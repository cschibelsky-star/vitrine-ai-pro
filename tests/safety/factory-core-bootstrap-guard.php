<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/Factory/Quality/Services/FactoryCoreBootstrapGuard.php';

use App\Factory\Quality\Services\FactoryCoreBootstrapGuard;

$guard = new FactoryCoreBootstrapGuard();
$guard->assertAllowed(true, 'FACTORY_CORE_BOOTSTRAP', 'hml', true);

$blocked = [
    [false, 'FACTORY_CORE_BOOTSTRAP', 'hml', true],
    [true, 'FACTORY_CORE_BOOTSTRAP', 'hml', false],
    [true, 'FACTORY_CORE_BOOTSTRAP', 'production', true],
    [true, 'FACTORY_CORE_BOOTSTRAP', 'prod', true],
    [true, 'WRONG', 'hml', true],
    [true, '', 'hml', true],
];

foreach ($blocked as $args) {
    try {
        $guard->assertAllowed(...$args);
        throw new LogicException('Unsafe bootstrap was accepted.');
    } catch (RuntimeException $expected) {
        if (! str_contains($expected->getMessage(), 'blocked') &&
            ! str_contains($expected->getMessage(), 'plan-only')) {
            throw $expected;
        }
    }
}

$command = file_get_contents(dirname(__DIR__, 2) . '/app/Factory/Console/Commands/FactoryInstallCommand.php');
$config = file_get_contents(dirname(__DIR__, 2) . '/config/factory.php');

if (! str_contains($command, '{--apply')
    || ! str_contains($command, '{--confirm=')
    || ! str_contains($command, "if (! \$apply)")
    || ! str_contains($command, "config('factory.core_bootstrap_enabled', false) === true")
    || ! str_contains($config, "env('FACTORY_CORE_BOOTSTRAP_ENABLED', false)")) {
    throw new RuntimeException('Bootstrap preflight contract is not implemented.');
}

$guardCall = strpos($command, '$guard->assertAllowed(');
$migrationCall = strpos($command, "['migrate', ['--force' => true]]");
if ($guardCall === false || $migrationCall === false || $guardCall > $migrationCall) {
    throw new RuntimeException('Migration scheduling precedes authorization.');
}

if (! str_contains($command, 'if ($exitCode !== self::SUCCESS)')) {
    throw new RuntimeException('Bootstrap does not fail-fast after migration, sync or seed errors.');
}
echo "PASS: plan-only default, opt-in, environment guard, explicit confirmation and fail-fast\n";
