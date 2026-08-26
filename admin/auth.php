<?php
if (getenv('VITRINE_ENABLE_PUBLIC_ADMIN') !== '1') {
    http_response_code(403);
    exit('Acesso administrativo indisponível neste ambiente.');
}

session_start();
if (empty($_SESSION['admin'])) {
    header('Location: /admin/login.php');
    exit;
}
require_once __DIR__.'/../includes/functions.php';
