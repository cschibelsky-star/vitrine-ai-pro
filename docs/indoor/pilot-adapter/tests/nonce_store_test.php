<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/IndoorServiceAuth.php';
require_once dirname(__DIR__) . '/src/IndoorRequestNonceStore.php';

function checkNonce(bool $condition, string $label): void {
    if (!$condition) throw new RuntimeException($label);
}
if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    throw new RuntimeException('CI must provide pdo_sqlite');
}
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE indoor_request_nonces (service_id TEXT NOT NULL, nonce_hash TEXT NOT NULL, expires_at INTEGER NOT NULL, PRIMARY KEY(service_id,nonce_hash))');
$source = new IndoorRequestNonceStore($db, 'tvsumare');
$sourceSecondWorker = new IndoorRequestNonceStore($db, 'tvsumare');
$otherService = new IndoorRequestNonceStore($db, 'other_tenant');
$nonce = str_repeat('b', 32);
$hash = hash('sha256', $nonce);
checkNonce($source->claim($hash, time()), 'Initial claim');
checkNonce(!$sourceSecondWorker->claim($hash, time()), 'Replay rejected across instances');
checkNonce($otherService->claim($hash, time()), 'Distinct service namespace');
checkNonce(!$source->claim('not-a-hash', time()), 'Invalid nonce hash rejected');
$secret = str_repeat('s', 32);
$ts = time();
$sig = IndoorServiceAuth::signature($secret, 'POST', '/api/indoor/v1/imports/tvsumare', '{}', $ts, str_repeat('c', 32));
$claim = static fn(string $h, int $t): bool => $source->claim($h, $t);
checkNonce(IndoorServiceAuth::verify($secret, 'POST', '/api/indoor/v1/imports/tvsumare', '{}', $ts, str_repeat('c',32), $sig, $claim, $ts), 'First signed request');
checkNonce(!IndoorServiceAuth::verify($secret, 'POST', '/api/indoor/v1/imports/tvsumare', '{}', $ts, str_repeat('c',32), $sig, $claim, $ts), 'Signed replay rejected');
echo "OK: shared PDO nonce claims and HMAC integration\n";
