<?php
require_once __DIR__ . '/../app/bootstrap.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string)($_POST['user'] ?? ''));
    $pass = (string)($_POST['pass'] ?? '');
    if (!admin_configured()) {
        $error = 'Admin ainda não configurado neste ambiente.';
    } elseif (admin_credentials_valid($user, $pass)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged'] = true;
        header('Location: /admin/index.php'); exit;
    } else {
        $error = 'Usuário ou senha inválidos.';
    }
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin — Conheça Sumaré</title><link rel="stylesheet" href="/admin/admin.css"></head><body><main class="wrap"><section class="card" style="max-width:480px;margin:auto"><h1>Admin Conheça Sumaré</h1><p class="muted">Gestão do Guia Digital da Cidade.</p><?php if($error): ?><div class="notice warning"><?= e($error) ?></div><?php endif; ?><?php if(!admin_configured()): ?><div class="notice warning">Acesso bloqueado até ADMIN_USER e ADMIN_PASSWORD_HASH serem configurados no runtime.</div><?php endif; ?><form method="post" class="form"><input name="user" autocomplete="username" placeholder="Usuário" required><input name="pass" autocomplete="current-password" placeholder="Senha" type="password" required><button class="btn">Entrar</button></form></section></main></body></html>