<?php

$routes = __DIR__ . '/routes/web.php';

if (! file_exists($routes)) {
    echo "ERRO: routes/web.php não encontrado.\n";
    exit(1);
}

$s = file_get_contents($routes);
$include = "if (file_exists(__DIR__.'/ai_provider_test.php')) {\n    require __DIR__.'/ai_provider_test.php';\n}";

if (! str_contains($s, "ai_provider_test.php")) {
    $s = rtrim($s) . "\n\n" . $include . "\n";
    file_put_contents($routes, $s);
    echo "Rota de teste de provedores registrada.\n";
} else {
    echo "Rota de teste de provedores já estava registrada.\n";
}

echo "MASTER 1.7 Execução Real aplicado.\n";
echo "Teste OpenAI: /admin/centro-ia/provedores/1/testar\n";
echo "Teste Gemini: /admin/centro-ia/provedores/2/testar\n";
