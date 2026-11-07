<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/Factory/FinalMaster/Services/FinalizationReportLocator.php';
use App\Factory\FinalMaster\Services\FinalizationReportLocator;

function attempt(callable $fn, bool $expectSuccess, string $label): void {
    try {
        $fn();
        if (!$expectSuccess) throw new LogicException("Unexpected acceptance: " . $label);
    } catch (RuntimeException $error) {
        if ($expectSuccess) throw $error;
        echo "PASS rejected: {$label}\n";
        return;
    }
    echo "PASS accepted: {$label}\n";
}
$root = sys_get_temp_dir() . '/factory-gate-' . bin2hex(random_bytes(6));
mkdir($root);
mkdir($root . '/mission-a');
mkdir($root . '/mission-b');
$pathA = $root . '/mission-a/finalization_report.json';
$pathB = $root . '/mission-b/finalization_report.json';
file_put_contents($pathA, json_encode(['request'=>'req-A','status'=>'finished','blueprint_slug'=>'blueprint-a']));
file_put_contents($pathB, json_encode(['request'=>'req-B','status'=>'finished','blueprint_slug'=>'blueprint-b']));
$locator = new FinalizationReportLocator();
try {
    attempt(fn () => $locator->resolve("FACTORY_FINALIZATION_REPORT_PATH={$pathA}\n", 'req-A', $root), true, 'own report');
    attempt(fn () => $locator->resolve("FACTORY_FINALIZATION_REPORT_PATH={$pathB}\n", 'req-A', $root), false, 'cross-request report');
    attempt(fn () => $locator->resolve('', 'req-A', $root), false, 'missing report token');
    attempt(fn () => $locator->resolve("FACTORY_FINALIZATION_REPORT_PATH={$pathA}\nFACTORY_FINALIZATION_REPORT_PATH={$pathB}\n", 'req-A', $root), false, 'ambiguous report token');
    attempt(fn () => $locator->resolve("FACTORY_FINALIZATION_REPORT_PATH=/etc/passwd\n", 'req-A', $root), false, 'outside path');
    file_put_contents($pathA, json_encode(['request'=>'req-A','status'=>'failed','blueprint_slug'=>'blueprint-a']));
    attempt(fn () => $locator->resolve("FACTORY_FINALIZATION_REPORT_PATH={$pathA}\n", 'req-A', $root), false, 'failed finalization');
} finally {
    unlink($pathA); unlink($pathB); rmdir($root . '/mission-a'); rmdir($root . '/mission-b'); rmdir($root);
}
