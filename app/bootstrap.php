<?php
declare(strict_types=1);
session_set_cookie_params(['secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/storage/data');
define('RUNTIME_ENV_FILE', APP_ROOT . '/.env.runtime');

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
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function runtime_value(string $key): string {
    if (is_file(RUNTIME_ENV_FILE)) {
        foreach (file(RUNTIME_ENV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            if (trim($k) === $key) return trim($v);
        }
    }
    $env = getenv($key);
    return $env === false ? '' : (string)$env;
}
function is_admin(): bool {
    if (isset($_SESSION['admin_sso_expires']) && (int)$_SESSION['admin_sso_expires'] < time()) {
        unset($_SESSION['admin_logged'], $_SESSION['admin_email'], $_SESSION['admin_sso_expires']);
    }
    return !empty($_SESSION['admin_logged']);
}
function require_admin(): void {
    if (!is_admin()) { header('Location: /admin/login.php'); exit; }
}
function admin_configured(): bool {
    return runtime_value('ADMIN_USER') !== '' && runtime_value('ADMIN_PASSWORD_HASH') !== '';
}
function admin_credentials_valid(string $user, string $pass): bool {
    $expectedUser = runtime_value('ADMIN_USER');
    $hash = runtime_value('ADMIN_PASSWORD_HASH');
    return $expectedUser !== '' && $hash !== '' && hash_equals($expectedUser, $user) && password_verify($pass, $hash);
}
