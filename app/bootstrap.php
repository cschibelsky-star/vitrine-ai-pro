<?php
declare(strict_types=1);
session_start();

define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/storage/data');
$eventsDataDir = trim((string)getenv('EVENTS_DATA_DIR'));
define('EVENTS_DATA_DIR', $eventsDataDir !== '' ? $eventsDataDir : DATA_DIR);

function data_read(string $file, array $default = []): array {
    $path = DATA_DIR . '/' . basename($file);
    if (!is_file($path)) return $default;
    $json = file_get_contents($path);
    $data = json_decode($json ?: '', true);
    return is_array($data) ? $data : $default;
}
function data_write(string $file, array $data): bool {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0775, true);
    $path = DATA_DIR . '/' . basename($file);
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false) return false;
    return rename($tmp, $path);
}
function events_read(array $default = []): array {
    $primary = EVENTS_DATA_DIR . '/events.json';
    $legacy = DATA_DIR . '/events.json';
    $path = is_file($primary) ? $primary : $legacy;
    if (!is_file($path)) return $default;
    $json = file_get_contents($path);
    $data = json_decode($json ?: '', true);
    return is_array($data) ? $data : $default;
}
function events_write(array $data): bool {
    if (!is_dir(EVENTS_DATA_DIR) && !mkdir(EVENTS_DATA_DIR, 0775, true) && !is_dir(EVENTS_DATA_DIR)) return false;
    $path = EVENTS_DATA_DIR . '/events.json';
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    if ($json === false || file_put_contents($tmp, $json . PHP_EOL, LOCK_EX) === false) return false;
    return rename($tmp, $path);
}
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function is_admin(): bool { return !empty($_SESSION['admin_logged']); }
function require_admin(): void {
    if (!is_admin()) { header('Location: /admin/login.php'); exit; }
}
function admin_configured(): bool {
    return (string)getenv('ADMIN_USER') !== '' && (string)getenv('ADMIN_PASSWORD_HASH') !== '';
}
function admin_credentials_valid(string $user, string $pass): bool {
    $expectedUser = (string)getenv('ADMIN_USER');
    $hash = (string)getenv('ADMIN_PASSWORD_HASH');
    return $expectedUser !== '' && $hash !== '' && hash_equals($expectedUser, $user) && password_verify($pass, $hash);
}
