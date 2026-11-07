<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/Factory/RealBuilder/Services/RealBuildPath.php';
require_once dirname(__DIR__, 2) . '/app/Factory/RealBuilder/Services/RunArtifactEvidence.php';

use App\Factory\RealBuilder\Services\RunArtifactEvidence;

$root = sys_get_temp_dir() . '/factory-evidence-' . bin2hex(random_bytes(6));
$runId = 'a21d35a0-d19e-4ac8-8ea2-d3654a743211';
$base = $root . '/escola/runs/' . $runId;
mkdir($base . '/app/Models', 0700, true);
$file = $base . '/app/Models/Aluno.php';
$reportPath = $base . '/REAL_BUILD_REPORT.json';
file_put_contents($file, '<?php class Aluno {}');
$report = [
    'blueprint'=>'escola', 'run_id'=>$runId, 'status'=>'generated',
    'files_count'=>1, 'files'=>[$file],
];
file_put_contents($reportPath, json_encode($report));
$collector = new RunArtifactEvidence();
try {
    $evidence = $collector->collect('escola', $runId, $root);
    if ($evidence['artifacts']['app/Models/Aluno.php'] !== hash_file('sha256',$file)
        || $evidence['qa_passed'] !== false || $evidence['via_approved'] !== false
        || $evidence['homologated'] !== false) {
        throw new RuntimeException('Expected unapproved hash evidence.');
    }
    try {
        $collector->collect('outra-escola', $runId, $root);
        throw new LogicException('Wrong blueprint was accepted.');
    } catch (RuntimeException $expected) {}
    $report['files'] = ['/etc/passwd'];
    file_put_contents($reportPath, json_encode($report));
    try {
        $collector->collect('escola', $runId, $root);
        throw new LogicException('Outside file was accepted.');
    } catch (RuntimeException $expected) {}
    $report['files'] = [$file];
    file_put_contents($reportPath, json_encode($report));
    unlink($file);
    try {
        $collector->collect('escola', $runId, $root);
        throw new LogicException('Missing artifact was accepted.');
    } catch (RuntimeException $expected) {}
    echo "PASS: hash evidence, mismatch, outside path, missing artifact and no implied approval\n";
} finally {
    if (is_file($file)) unlink($file);
    unlink($reportPath);
    rmdir($base . '/app/Models');
    rmdir($base . '/app');
    rmdir($base);
    rmdir($root . '/escola/runs');
    rmdir($root . '/escola');
    rmdir($root);
}
