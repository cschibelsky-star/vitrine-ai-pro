<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/IndoorServiceAuth.php';
$url = 'http://127.0.0.1:18743/api/indoor/v1/imports/tvsumare';
$path = '/api/indoor/v1/imports/tvsumare';
$secret = getenv('INDOOR_TEST_SECRET');
if (!is_string($secret) || strlen($secret) < 32) throw new RuntimeException('Missing test-only secret');
function requestLocal(string $url, array $headers, string $body): array {
    $context = stream_context_create(['http' => [
        'method' => 'POST', 'header' => implode("\r\n", $headers),
        'content' => $body, 'ignore_errors' => true, 'timeout' => 5
    ]]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) throw new RuntimeException('Local-only test server unreachable');
    $status = (int)preg_replace('/^HTTP\\/[\\d.]+\\s+(\\d+).*$/', '$1', $http_response_header[0] ?? '');
    return [$status, json_decode($response, true)];
}
$ts = time();
$nonce = bin2hex(random_bytes(16));
$body = '{"tenant_id":"tenant_other"}';
$signature = IndoorServiceAuth::signature($secret, 'POST', $path, $body, $ts, $nonce);
$headers = [
    'Content-Type: application/json',
    'X-Indoor-Service: tvsumare_hml',
    'X-Indoor-Timestamp: ' . $ts,
    'X-Indoor-Nonce: ' . $nonce,
    'X-Indoor-Signature: ' . $signature
];
[$status, $data] = requestLocal($url, $headers, $body);
if ($status !== 200 || ($data['tenant_id'] ?? '') !== 'tenant_tvsumare'
    || ($data['import_state'] ?? '') !== 'pending_indoor_review') {
    throw new RuntimeException('Signed request did not bind to server-owned tenant');
}
[$status] = requestLocal($url, $headers, $body);
if ($status !== 401) throw new RuntimeException('Replay accepted over HTTP');
[$status] = requestLocal($url, $headers, '{"tenant_id":"tenant_tvsumare"}');
if ($status !== 401) throw new RuntimeException('Modified body accepted over HTTP');
$wrong = $headers;
$wrong[1] = 'X-Indoor-Service: other_hml';
[$status] = requestLocal($url, $wrong, $body);
if ($status !== 401) throw new RuntimeException('Cross-service request accepted');
echo "OK: localhost HTTP signed tenant binding and replay rejection\n";
