<?php

$routes = __DIR__ . '/routes/web.php';

if (! file_exists($routes)) {
    echo "ERRO: routes/web.php não encontrado.\n";
    exit(1);
}

$s = file_get_contents($routes);
$include = "if (file_exists(__DIR__.'/asaas.php')) {\n    require __DIR__.'/asaas.php';\n}";

if (! str_contains($s, "asaas.php")) {
    $s = rtrim($s) . "\n\n" . $include . "\n";
    file_put_contents($routes, $s);
    echo "Rotas Asaas registradas.\n";
} else {
    echo "Rotas Asaas já estavam registradas.\n";
}

echo "MASTER 1.6 Asaas Webhook aplicado.\n";
echo "Endpoint: /api/asaas/webhook\n";
