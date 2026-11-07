<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/app/Factory/Quality/Services/FactoryViaObserverHandoffDraft.php';
use App\Factory\Quality\Services\FactoryViaObserverHandoffDraft;

function check(bool $condition, string $reason): void {
    if (!$condition) throw new RuntimeException($reason);
}

$builder = new FactoryViaObserverHandoffDraft();
$runId = 'a21d35a0-d19e-4ac8-8ea2-d3654a743211';
$base = [
    'run_id' => $runId,
    'blueprint' => 'escola',
    'decision' => 'integrity_verified_review_pending',
    'can_advance' => false,
];
$draft = $builder->fromIntegrity($base, 'cschibelsky-star/vitrine-ai-pro', 'feature/test');
check($draft['schema_version'] === '1.0', 'Schema mismatch');
check($draft['state'] !== 'ready_for_via', 'Premature ready_for_via');
check($draft['can_advance'] === false, 'Premature advancement');
check($draft['authorization']['approved_for_branch_write'] === false, 'Fake branch authorization');
check($draft['authorization']['production_approved'] === false, 'Fake production authorization');
check($draft['checks'][0]['status'] === 'not_run', 'Fake tests');
check($draft['review']['status'] === 'pending', 'Fake review');
check($draft['base_sha'] === null && $draft['result_sha'] === null, 'Fake git provenance');
check($draft['factory_artifact']['independent_integrity_checked'] === true, 'Integrity status lost');

$blocked = $base;
$blocked['decision'] = 'blocked_integrity_mismatch';
check($builder->fromIntegrity($blocked, 'cschibelsky-star/vitrine-ai-pro', 'feature/test')['factory_artifact']['independent_integrity_checked'] === false, 'Blocked was labelled verified');

foreach ([
    ['run_id' => '../bad'],
    ['blueprint' => '../../bad'],
    ['can_advance' => true],
    ['decision' => 'verified_and_approved'],
] as $mutation) {
    try {
        $builder->fromIntegrity(array_replace($base, $mutation), 'cschibelsky-star/vitrine-ai-pro', 'feature/test');
        throw new LogicException('Unsafe handoff draft accepted');
    } catch (InvalidArgumentException $expected) {}
}

try {
    $builder->fromIntegrity($base, 'bad/repo/path', 'feature/test');
    throw new LogicException('Unsafe repository accepted');
} catch (InvalidArgumentException $expected) {}

echo "PASS: VIA handoff drafts stay pending, reject malformed integrity and never forge approvals\n";
