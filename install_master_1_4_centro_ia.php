<?php

$base = __DIR__;
$providerPath = $base . '/app/Providers/Filament/AdminPanelProvider.php';

if (! file_exists($providerPath)) {
    echo "ERRO: AdminPanelProvider.php não encontrado.\n";
    exit(1);
}

$s = file_get_contents($providerPath);

if (str_contains($s, 'App\\Filament\\Pages\\AiDashboard')) {
    echo "AiDashboard já referenciado no provider.\n";
} else {
    $needleOptions = [
        '->discoverResources(',
        '->discoverPages(',
        '->discoverWidgets(',
        '->widgets(',
        '->middleware(',
    ];

    $insert = "->pages([\\App\\Filament\\Pages\\AiDashboard::class])\n            ";

    $done = false;
    foreach ($needleOptions as $needle) {
        $pos = strpos($s, $needle);
        if ($pos !== false) {
            $s = substr($s, 0, $pos) . $insert . substr($s, $pos);
            $done = true;
            break;
        }
    }

    if (! $done) {
        echo "ERRO: não consegui localizar ponto seguro para inserir pages().\n";
        exit(1);
    }

    file_put_contents($providerPath, $s);
    echo "AiDashboard registrado em AdminPanelProvider.php.\n";
}

echo "Instalação MASTER 1.4 aplicada.\n";
