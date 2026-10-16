<?php
/**
 * Preview isolado: reaproveita integralmente a Home e injeta apenas CSS de teste.
 * Não usar como rota oficial de produção; publicar somente após validação HML.
 */
$originalScript = $_SERVER['SCRIPT_NAME'] ?? '';
$_SERVER['SCRIPT_NAME'] = '/index.php';
ob_start();
require __DIR__ . '/index.php';
$html = ob_get_clean();
$_SERVER['SCRIPT_NAME'] = $originalScript;
$stylesheet = '<link rel="stylesheet" href="/assets/css/home-institucional-preview.css?v=1">';
$html = str_replace('</head>', $stylesheet . '</head>', $html);
echo $html;
