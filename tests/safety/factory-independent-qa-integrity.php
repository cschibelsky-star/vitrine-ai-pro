<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/Factory/RealBuilder/Services/RealBuildPath.php';
require_once dirname(__DIR__, 2) . '/app/Factory/RealBuilder/Services/RunArtifactEvidence.php';
require_once dirname(__DIR__, 2) . '/app/Factory/Quality/Services/IndependentArtifactIntegrityGate.php';

use App\Factory\RealBuilder\Services\RunArtifactEvidence;
use App\Factory\Quality\Services\IndependentArtifactIntegrityGate;

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$root = sys_get_temp_dir() . '/factory-independent-qa-' . bin2hex(random_bytes(6));
$runId = 'a21d35a0-d19e-4ac8-8ea2-d3654a743211';
$base = $root . '/escola/runs/' . $runId;
mkdir($base . '/app/Models', 0700, true);
$file = $base . '/app/Models/Aluno.php';
$reportFile = $base . '/REAL_BUILD_REPORT.json';
file_put_contents($file, '<?php class Aluno {}');
file_put_contents($reportFile, json_encode([
    'blueprint'=>'escola', 'run_id'=>$runId, 'status'=>'generated',
    'files_count'=>1, 'files'=>[$file],
]));
$collector = new RunArtifactEvidence();
$gate = new IndependentArtifactIntegrityGate($collector);
try {
    $reference = $collector->collect('escola', $runId, $root);
    $decision = $gate->verify($reference, $root);
    check($decision['decision'] === 'integrity_verified_review_pending', 'Valid integrity was not recognized.');
    check($decision['can_advance'] === false && $decision['qa_passed'] === false && $decision['via_approved'] === false, 'Integrity improperly granted approval.');

    file_put_contents($file, '<?php class Aluno { public $changed = true; }');
    $decision = $gate->verify($reference, $root);
    check($decision['decision'] === 'blocked_integrity_mismatch', 'Artifact tampering was accepted.');

    file_put_contents($file, '<?php class Aluno {}');
    $forged = $reference;
    $forged['artifacts']['app/Models/Aluno.php'] = str_repeat('a', 64);
    check($gate->verify($forged, $root)['decision'] === 'blocked_integrity_mismatch', 'Forged hash accepted.');

    $forged = $reference;
    $forged['run_id'] = 'b21d35a0-d19e-4ac8-8ea2-d3654a743211';
    check($gate->verify($forged, $root)['can_advance'] === false, 'Wrong run advanced.');

    unlink($file);
    check($gate->verify($reference, $root)['decision'] === 'blocked_missing_or_unsafe_artifact', 'Missing file not blocked.');

    echo "PASS: independent integrity only, no QA/VIA authorization, mutations and missing files blocked\n";
} finally {
    if (is_file($file)) unlink($file);
    unlink($reportFile);
    rmdir($base . '/app/Models'); rmdir($base . '/app'); rmdir($base);
    rmdir($root . '/escola/runs'); rmdir($root . '/escola'); rmdir($root);
}
