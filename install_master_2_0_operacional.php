<?php

$routes = __DIR__ . '/routes/web.php';

if (! file_exists($routes)) {
    echo "ERRO: routes/web.php não encontrado.\n";
    exit(1);
}

$s = file_get_contents($routes);
$include = "if (file_exists(__DIR__.'/master_2_0.php')) {\n    require __DIR__.'/master_2_0.php';\n}";

if (! str_contains($s, "master_2_0.php")) {
    $s = rtrim($s) . "\n\n" . $include . "\n";
    file_put_contents($routes, $s);
    echo "Rotas MASTER 2.0 registradas.\n";
} else {
    echo "Rotas MASTER 2.0 já estavam registradas.\n";
}

echo "MASTER 2.0 Operacional aplicado.\n";
echo "Agora rode: php artisan migrate --force\n";
echo "Depois: php artisan optimize:clear\n";
