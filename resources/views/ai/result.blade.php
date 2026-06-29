<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Resultado IA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{font-family:Arial,sans-serif;background:#f6f8fb;margin:0;padding:30px;color:#0f172a}
        .box{max-width:1000px;margin:auto;background:white;border-radius:16px;padding:24px;box-shadow:0 10px 30px #0001}
        pre{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:18px;border-radius:14px}
        .pill{display:inline-block;padding:5px 10px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-weight:700}
        a{color:#2563eb}
    </style>
</head>
<body>
<div class="box">
    <h1>Resultado da Execução</h1>
    <p><strong>Agente:</strong> {{ $agent->name }}</p>
    <p><strong>Status:</strong> <span class="pill">{{ $execution->status }}</span></p>

    <h2>Entrada</h2>
    <pre>{{ $execution->input }}</pre>

    <h2>Resposta</h2>
    <pre>{{ $execution->output }}</pre>

    <p><a href="/admin/centro-ia/executar">Executar novamente</a> | <a href="/admin/ai-executions">Ver execuções</a> | <a href="/admin">Admin</a></p>
</div>
</body>
</html>
