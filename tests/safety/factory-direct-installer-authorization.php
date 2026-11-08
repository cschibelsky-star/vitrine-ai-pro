<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/Factory/Quality/Services/FactoryInstallAuthorizationGate.php';
require_once dirname(__DIR__, 2) . '/app/Factory/RealBuilder/Services/RealBuildInstaller.php';
require_once dirname(__DIR__, 2) . '/app/Factory/EnterpriseMaturity/Services/EnterpriseBuildInstaller.php';

use App\Factory\RealBuilder\Services\RealBuildInstaller;
use App\Factory\EnterpriseMaturity\Services\EnterpriseBuildInstaller;

foreach ([new RealBuildInstaller(), new EnterpriseBuildInstaller()] as $installer) {
    try {
        $installer->install('../never-touch-project', false, true);
        throw new LogicException('Direct install must be blocked');
    } catch (RuntimeException $expected) {
        if (!str_contains($expected->getMessage(), 'blocked')) {
            throw $expected;
        }
    }
}

foreach ([
    'app/Factory/RealBuilder/Services/RealBuildInstaller.php',
    'app/Factory/EnterpriseMaturity/Services/EnterpriseBuildInstaller.php',
] as $path) {
    $content = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
    $entrypoint = strpos($content, 'public function install(');
    $gate = strpos($content, '->assertAllowed($dryRun)', $entrypoint);
    $filesystem = strpos($content, "storage_path('app/factory/", $entrypoint);
    if ($entrypoint === false || $gate === false || $filesystem === false
        || $gate > $filesystem) {
        throw new RuntimeException("Installer gate must precede filesystem access: {$path}");
    }
}

echo "PASS: both direct installer services reject real install before filesystem access\n";
