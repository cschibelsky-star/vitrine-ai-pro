<?php
declare(strict_types=1);
// CI ONLY. Must be launched with PHP built-in server bound to 127.0.0.1.
// No deployment or application routing must include this file.
if (PHP_SAPI !== 'cli-server' || ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/src/IndoorServiceGateway.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
$path = '/api/indoor/v1/imports/tvsumare';
if (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) !== $path) {
    http_response_code(404); echo '{"error":"not_found"}'; exit;
}
$dbFile = getenv('INDOOR_TEST_NONCE_DB');
$secret = getenv('INDOOR_TEST_SECRET');
if (!is_string($dbFile) || $dbFile === '' || !is_string($secret) || strlen($secret) < 32) {
    http_response_code(503); echo '{"error":"test_not_configured"}'; exit;
}
$db = new PDO('sqlite:' . $dbFile);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$credentials = [
    'tvsumare_hml' => [
        'secret' => $secret, 'tenant_id' => 'tenant_tvsumare',
        'enabled' => true, 'allowed_path' => $path
    ]
];
$ts = $_SERVER['HTTP_X_INDOOR_TIMESTAMP'] ?? '';
$identity = ctype_digit((string)$ts) ? IndoorServiceGateway::authorize(
    $credentials,
    $db,
    (string)($_SERVER['HTTP_X_INDOOR_SERVICE'] ?? ''),
    (string)($_SERVER['REQUEST_METHOD'] ?? ''),
    (string)($_SERVER['REQUEST_URI'] ?? ''),
    (string)file_get_contents('php://input'),
    (int)$ts,
    (string)($_SERVER['HTTP_X_INDOOR_NONCE'] ?? ''),
    (string)($_SERVER['HTTP_X_INDOOR_SIGNATURE'] ?? '')
) : null;
if ($identity === null) {
    http_response_code(401); echo '{"error":"unauthorized"}'; exit;
}
http_response_code(200);
echo json_encode(['tenant_id' => $identity['tenant_id'], 'import_state' => 'pending_indoor_review']);
