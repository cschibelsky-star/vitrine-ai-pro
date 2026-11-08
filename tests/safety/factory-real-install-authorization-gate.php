<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/Factory/Quality/Services/FactoryInstallAuthorizationGate.php';
use App\Factory\Quality\Services\FactoryInstallAuthorizationGate;

$gate = new FactoryInstallAuthorizationGate();
$gate->assertAllowed(true);

foreach ([[], ['qa_passed'=>true, 'via_approved'=>true, 'human_authorized'=>true], ['force'=>true]] as $claimedProof) {
    try {
        $gate->assertAllowed(false, $claimedProof);
        throw new LogicException('Untrusted approvals allowed a real installation.');
    } catch (RuntimeException $expected) {
        if (!str_contains($expected->getMessage(), 'blocked')) {
            throw $expected;
        }
    }
}

$source = file_get_contents(dirname(__DIR__, 2) . '/app/Factory/FinalMaster/Services/FactoryFinalMasterService.php');
$gateCall = strpos($source, '->assertAllowed($dryRun)');
$runId = strpos($source, '$runId = (string) Str::uuid()');
$directory = strpos($source, 'File::ensureDirectoryExists($stepsDir)');
$finalize = strpos($source, "\$this->call('factory:finalize-request'");
if ($gateCall === false || $runId === false || $directory === false || $finalize === false
    || !($gateCall < $runId && $gateCall < $directory && $gateCall < $finalize)) {
    throw new RuntimeException('Install gate must run before all FinalMaster side effects.');
}
echo "PASS: dry-run allowed, real installs blocked despite forged flags, preflight before side effects\n";
