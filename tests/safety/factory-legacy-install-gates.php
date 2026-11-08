<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/Factory/Quality/Services/FactoryInstallAuthorizationGate.php';
require_once dirname(__DIR__, 2) . '/app/Factory/Builder/Services/ModuleInstaller.php';
require_once dirname(__DIR__, 2) . '/app/Factory/Finalization/Services/SmartFinalInstallerService.php';
require_once dirname(__DIR__, 2) . '/app/Factory/FinalProducer/Services/SystemInstallPlanner.php';

use App\Factory\Builder\Services\ModuleInstaller;
use App\Factory\Finalization\Services\SmartFinalInstallerService;
use App\Factory\FinalProducer\Services\SystemInstallPlanner;

$cases = [
    [new ModuleInstaller(), 'install', ['../invalid', false, true]],
    [new SmartFinalInstallerService(), 'install', ['../invalid', false, true, true]],
    [new SystemInstallPlanner(), 'plan', ['../invalid', false, true]],
];
foreach ($cases as [$service, $method, $args]) {
    try {
        $service->{$method}(...$args);
        throw new LogicException('Direct installation bypassed gate: ' . $service::class);
    } catch (RuntimeException $expected) {
        if (!str_contains($expected->getMessage(), 'blocked')) {
            throw $expected;
        }
    }
}

foreach ([
    'app/Factory/Builder/Services/ModuleInstaller.php',
    'app/Factory/Finalization/Services/SmartFinalInstallerService.php',
    'app/Factory/FinalProducer/Services/SystemInstallPlanner.php',
] as $path) {
    $src = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
    $method = strpos($src, 'public function ');
    $gate = strpos($src, '->assertAllowed($dryRun)', $method);
    $storage = strpos($src, "storage_path('app/factory/", $method);
    if ($method === false || $gate === false || $storage === false || $gate > $storage) {
        throw new RuntimeException('Gate must precede storage access: ' . $path);
    }
}

echo "PASS: module, final, and planner real installs fail closed before filesystem access\n";
