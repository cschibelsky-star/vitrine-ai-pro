<?php
require_once __DIR__ . '/../app/bootstrap.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
if (is_admin()) { header('Location: /admin/index.php'); exit; }
$error = (string)($_SESSION['admin_login_error'] ?? '');
unset($_SESSION['admin_login_error']);
if (isset($_GET['token'])) {
    try {
        $token = (string)$_GET['token'];
        $state = (string)($_GET['state'] ?? '');
        $expectedState = (string)($_SESSION['cockpit_sso_state'] ?? '');
        $started = (int)($_SESSION['cockpit_sso_started'] ?? 0);
        unset($_SESSION['cockpit_sso_state'], $_SESSION['cockpit_sso_started']);
        if (!preg_match('/^[A-Za-z0-9]{64}$/D', $token) || !preg_match('/^[a-f0-9]{64}$/D', $state) || $expectedState === '' || !hash_equals($expectedState, $state) || time() - $started > 600) throw new RuntimeException('Inicie o acesso pelo botão da Vitrine IA Pro para continuar.');
        $context = stream_context_create(['http' => ['timeout' => 8, 'follow_location' => 0, 'header' => "Accept: application/json\r\n"], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $json = @file_get_contents('https://hml.vitrineiapro.com.br/cockpit/sso/consume?token=' . rawurlencode($token), false, $context);
        if ($json === false || !isset($http_response_header[0]) || !preg_match('/\s200\s/', $http_response_header[0])) throw new RuntimeException('O acesso expirou ou o Cockpit está indisponível. Tente novamente.');
        $payload = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($payload) || ($payload['target'] ?? '') !== 'guia-digital' || ($payload['role'] ?? '') !== 'admin' || !filter_var($payload['email'] ?? '', FILTER_VALIDATE_EMAIL) || !is_int($payload['issued_at'] ?? null) || time() - $payload['issued_at'] > 65 || $payload['issued_at'] > time() + 5) throw new RuntimeException('Acesso administrativo não autorizado para este guia.');
        session_regenerate_id(true);
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_email'] = $payload['email'];
        $_SESSION['admin_sso_expires'] = time() + 7200;
        header('Location: /admin/index.php', true, 303);
        exit;
    } catch (Throwable $exception) {
        $_SESSION['admin_login_error'] = $exception instanceof RuntimeException ? $exception->getMessage() : 'Não foi possível validar o acesso. Tente novamente.';
        header('Location: /admin/login.php', true, 303);
        exit;
    }
}
if (($_GET['sso'] ?? '') === 'start') {
    $_SESSION['cockpit_sso_state'] = bin2hex(random_bytes(32));
    $_SESSION['cockpit_sso_started'] = time();
    header('Location: https://hml.vitrineiapro.com.br/cockpit/open/guia-digital?state=' . $_SESSION['cockpit_sso_state']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && admin_configured()) {
    $user = trim((string)($_POST['user'] ?? ''));
    if (admin_credentials_valid($user, (string)($_POST['pass'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['admin_logged'] = true;
        $_SESSION['admin_email'] = $user;
        header('Location: /admin/index.php'); exit;
    }
    $error = 'Usuário ou senha inválidos.';
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin — Conheça Sumaré</title><link rel="stylesheet" href="/admin/admin.css"></head><body><main class="wrap"><section class="card" style="max-width:480px;margin:auto"><h1>Admin Conheça Sumaré</h1><p class="muted">Gestão do Guia Digital da Cidade.</p><?php if ($error): ?><div class="notice warning" role="alert"><?=e($error)?></div><?php endif; ?><p>Use sua conta administrativa da Vitrine IA Pro para revisar e publicar eventos.</p><a class="btn" href="/admin/login.php?sso=start">Entrar com Vitrine IA Pro</a><?php if (admin_configured()): ?><hr><form method="post" class="form"><input name="user" autocomplete="username" placeholder="Usuário local" required><input name="pass" autocomplete="current-password" placeholder="Senha" type="password" required><button class="btn">Entrar com acesso local</button></form><?php endif; ?></section></main></body></html>
