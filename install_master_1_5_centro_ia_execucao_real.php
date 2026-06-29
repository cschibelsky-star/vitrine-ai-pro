<?php

$routes = __DIR__ . '/routes/web.php';

if (! file_exists($routes)) {
    echo "ERRO: routes/web.php não encontrado.\n";
    exit(1);
}

$s = file_get_contents($routes);
$include = "if (file_exists(__DIR__.'/ai_run.php')) {\n    require __DIR__.'/ai_run.php';\n}";

if (! str_contains($s, "ai_run.php")) {
    $s = rtrim($s) . "\n\n" . $include . "\n";
    file_put_contents($routes, $s);
    echo "Rotas de execução IA registradas.\n";
} else {
    echo "Rotas de execução IA já estavam registradas.\n";
}

echo "MASTER 1.5 Centro IA Execução Real aplicado.\n";
echo "Teste: /admin/centro-ia/executar\n";
