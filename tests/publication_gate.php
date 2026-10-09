<?php

declare(strict_types=1);

require __DIR__.'/../app/Services/Deploy/HomologationGate.php';
require __DIR__.'/../app/Services/Deploy/ReleaseLedger.php';
require __DIR__.'/../app/Services/Deploy/SignedEvidence.php';
require __DIR__.'/../app/Services/Deploy/PublicationControl.php';

use App\Services\Deploy\HomologationGate;
use App\Services\Deploy\PublicationControl;
use App\Services\Deploy\ReleaseLedger;
use App\Services\Deploy\SignedEvidence;

$count = 0;
function check(bool $value, string $message): void {
    global $count;
    $count++;
    if (!$value) { throw new RuntimeException($message); }
}
$sha = str_repeat('a', 40);
$root = sys_get_temp_dir().'/publication-'.bin2hex(random_bytes(8));
mkdir($root, 0700);
$checks = [
    'hml_url' => 'https://p000002.hml.vitrineiapro.com.br', 'production_url' => 'https://www.conhecasumare.com.br',
    'tested_sha' => $sha, 'target_sha' => $sha,
];
foreach (['hml_dns_ok', 'hml_tls_ok', 'hml_health_ok', 'isolated_runtime', 'isolated_data', 'ci_green', 'tests_passed', 'backup_verified', 'rollback_ready'] as $key) { $checks[$key] = true; }
$evidence = ['checks' => $checks, 'digest' => str_repeat('c', 64), 'executor' => 'conheca-sumare-vps'];
$actors = ['42' => true];
$unavailable = false;
$control = new PublicationControl(new HomologationGate(), new ReleaseLedger($root),
    function () use (&$evidence, &$unavailable): array {
        if ($unavailable) { throw new RuntimeException('unavailable'); }
        return $evidence;
    }, function ($actor) use (&$actors): bool { return $actors[$actor] ?? false; });
$mutations = 0;
// A real executor calls this boundary before its mutating callback.
$publish = function (string $target) use ($control, &$mutations): bool {
    $result = $control->act('conheca-sumare', $target, 'consume', 'executor', 'conheca-sumare-vps');
    if ($result['allowed']) { $mutations++; }
    return $result['allowed'];
};
try {
    check(!$publish($sha) && $mutations === 0, 'No approval must block the executor');
    check(!$control->act('conheca-sumare', $sha, 'approve', 'client')['allowed'], 'Client cannot approve');
    foreach (array_keys($checks) as $field) {
        if (!is_bool($checks[$field])) { continue; }
        $evidence['checks'][$field] = false;
        check(!$control->act('conheca-sumare', $sha, 'approve', '42')['allowed'], 'Unverified '.$field.' blocks approval');
        $evidence['checks'][$field] = true;
    }
    check($control->act('conheca-sumare', $sha, 'approve', '42')['allowed'], 'Verified evidence can be approved');
    $evidence['checks']['target_sha'] = str_repeat('b', 40);
    check(!$publish($sha) && $mutations === 0, 'New commit must block the old release');
    $evidence['checks']['target_sha'] = $sha;
    check(!$publish($sha), 'Returning branch to old SHA cannot restore invalidated approval');
    $control->act('conheca-sumare', $sha, 'approve', '42');
    $control->invalidate('conheca-sumare', str_repeat('b', 40));
    check(!$publish($sha), 'Push webhook invalidates approval');
    $control->act('conheca-sumare', $sha, 'approve', '42');
    $actors['42'] = false;
    check(!$publish($sha), 'Revoked admin role invalidates approval');
    $actors['42'] = true;
    $control->act('conheca-sumare', $sha, 'approve', '42');
    $unavailable = true;
    check(!$publish($sha), 'Collector outage fails closed');
    $unavailable = false;
    $control->act('conheca-sumare', $sha, 'approve', '42');
    check(!$control->act('conheca-sumare', $sha, 'consume', 'executor', 'other')['allowed'], 'Wrong executor blocked');
    check($publish($sha) && $mutations === 1, 'Approved exact SHA invokes the executor');
    check(!$publish($sha) && $mutations === 1, 'Approval cannot be replayed');
    $expired = new PublicationControl(new HomologationGate(), new ReleaseLedger($root), fn () => $evidence, fn () => true, -1);
    $expired->act('conheca-sumare', $sha, 'approve', '42');
    check(!$expired->act('conheca-sumare', $sha, 'consume', 'executor', 'conheca-sumare-vps')['allowed'], 'Expired approval blocked');

    $keys = sodium_crypto_sign_keypair();
    $public = base64_encode(sodium_crypto_sign_publickey($keys));
    $payload = json_encode(['project_id' => 'conheca-sumare', 'observed_at' => time()], JSON_THROW_ON_ERROR);
    $write = function (string $payload, string $signature) use ($root): void {
        file_put_contents($root.'/conheca-sumare.json', json_encode(['payload' => base64_encode($payload), 'signature' => base64_encode($signature)]));
    };
    $signature = sodium_crypto_sign_detached($payload, sodium_crypto_sign_secretkey($keys));
    $write($payload, $signature);
    $signed = new SignedEvidence($root, $public);
    check($signed->read('conheca-sumare')['digest'] === hash('sha256', $payload), 'Signature verified');
    $write($payload.' ', $signature);
    try { $signed->read('conheca-sumare'); check(false, 'Tampered evidence accepted'); } catch (RuntimeException $e) { check($e->getMessage() === 'invalid_collector_signature', 'Tampered evidence rejected'); }
    $old = json_encode(['project_id' => 'conheca-sumare', 'observed_at' => time() - 1000]);
    $write($old, sodium_crypto_sign_detached($old, sodium_crypto_sign_secretkey($keys)));
    try { $signed->read('conheca-sumare'); check(false, 'Stale evidence accepted'); } catch (RuntimeException $e) { check($e->getMessage() === 'stale_or_wrong_evidence', 'Stale evidence rejected'); }
    echo "OK: $count publication checks; unauthorized mutations: 0\n";
} finally {
    foreach (glob($root.'/*') as $file) { unlink($file); }
    rmdir($root);
}
