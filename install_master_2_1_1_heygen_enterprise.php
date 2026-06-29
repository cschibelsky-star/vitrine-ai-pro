<?php

$routes = __DIR__ . '/routes/web.php';
if (! file_exists($routes)) {
    echo "ERRO: routes/web.php não encontrado.\n";
    exit(1);
}

$s = file_get_contents($routes);
$include = "if (file_exists(__DIR__.'/heygen_callback.php')) {\n    require __DIR__.'/heygen_callback.php';\n}";

if (! str_contains($s, "heygen_callback.php")) {
    $s = rtrim($s) . "\n\n" . $include . "\n";
    file_put_contents($routes, $s);
    echo "Rotas callback HeyGen registradas.\n";
} else {
    echo "Rotas callback HeyGen já estavam registradas.\n";
}

echo "MASTER 2.1.1 HeyGen Enterprise aplicado.\n";
