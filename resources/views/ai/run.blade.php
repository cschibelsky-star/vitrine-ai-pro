<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Executar Agente IA</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{font-family:Arial,sans-serif;background:#f6f8fb;margin:0;padding:30px;color:#0f172a}
        .box{max-width:900px;margin:auto;background:white;border-radius:16px;padding:24px;box-shadow:0 10px 30px #0001}
        label{display:block;margin-top:14px;font-weight:700}
        select,textarea{width:100%;box-sizing:border-box;padding:12px;border:1px solid #cbd5e1;border-radius:12px;margin-top:6px}
        textarea{min-height:180px}
        button{margin-top:18px;background:#2563eb;color:white;border:0;padding:12px 18px;border-radius:12px;font-weight:700;cursor:pointer}
        a{color:#2563eb}
    </style>
</head>
<body>
<div class="box">
    <h1>Executar Agente IA</h1>
    <p>Teste operacional da Build 1.5.</p>

    <form method="post" action="/admin/centro-ia/executar">
        @csrf
        <label>Agente</label>
        <select name="ai_agent_id" required>
            @foreach($agents as $agent)
                <option value="{{ $agent->id }}">{{ $agent->name }} — {{ $agent->status }}</option>
            @endforeach
        </select>

        <label>Prompt / Solicitação</label>
        <textarea name="prompt" required>Cliente: Prefeitura
Necessidade: portal de notícias automatizado com IA, vídeos e transmissão ao vivo.
Objetivo: recomendar produto, plano e próxima ação comercial.</textarea>

        <button type="submit">Executar Agente</button>
    </form>

    <p><a href="/admin">Voltar ao Admin</a></p>
</div>
</body>
</html>
