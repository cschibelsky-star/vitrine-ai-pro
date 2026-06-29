<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Área do Cliente — Vitrine AI Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin:0; font-family: Arial, sans-serif; background:#f5f7fb; color:#0f172a; }
        .hero { background: linear-gradient(135deg,#0f172a,#1d4ed8,#06b6d4); color:#fff; padding:32px; }
        .wrap { max-width:1180px; margin:0 auto; padding:24px; }
        .hero .wrap { padding:0; position:relative; }
        .logout { position:absolute; right:0; top:0; background:rgba(255,255,255,.16); color:#fff; padding:9px 16px; border-radius:999px; text-decoration:none; font-weight:700; font-size:13px; }
        .badge { display:inline-block; background:rgba(255,255,255,.15); border-radius:999px; padding:6px 12px; font-size:12px; font-weight:700; text-transform:uppercase; }
        h1 { margin:12px 0 6px; font-size:32px; }
        .subtitle { color:#dbeafe; }
        .grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-top:20px; }
        .card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:18px; box-shadow:0 12px 28px rgba(15,23,42,.06); }
        .card h2 { margin:0 0 12px; font-size:18px; }
        .metric { font-size:28px; font-weight:800; }
        .muted { color:#64748b; font-size:13px; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        th, td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:top; }
        th { color:#475569; font-size:12px; text-transform:uppercase; }
        .span-2 { grid-column:span 2; }
        .span-4 { grid-column:span 4; }
        .pill { display:inline-block; padding:4px 8px; border-radius:999px; background:#eff6ff; color:#1d4ed8; font-size:12px; font-weight:700; }
        .success { background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; padding:12px 14px; border-radius:14px; margin-bottom:16px; }
        label { display:block; margin:12px 0 6px; font-weight:700; font-size:14px; }
        input, textarea, select { width:100%; box-sizing:border-box; padding:12px 13px; border:1px solid #cbd5e1; border-radius:12px; font-size:14px; }
        textarea { min-height:110px; }
        button { margin-top:14px; padding:12px 18px; border:0; border-radius:12px; background:#2563eb; color:#fff; font-weight:800; cursor:pointer; }
        @media (max-width:900px) { .grid { grid-template-columns:1fr; } .span-2,.span-4 { grid-column:span 1; } .logout { position:static; display:inline-block; margin-top:14px; } }
    </style>
</head>
<body>
    <section class="hero">
        <div class="wrap">
            <a class="logout" href="/cliente/logout">Sair</a>
            <span class="badge">Área do Cliente</span>
            <h1>{{ $companyName ?? 'Cliente' }}</h1>
            <div class="subtitle">Resumo do seu projeto, plano, módulos, contratos, cobranças e chamados na Vitrine AI Pro.</div>
        </div>
    </section>

    <main class="wrap">
        @if (session('success'))
            <div class="success">{{ session('success') }}</div>
        @endif

        <div class="grid">
            <div class="card"><div class="muted">Licenças</div><div class="metric">{{ $licenses->count() }}</div></div>
            <div class="card"><div class="muted">Módulos ativos</div><div class="metric">{{ $modules->count() }}</div></div>
            <div class="card"><div class="muted">Contratos</div><div class="metric">{{ $contracts->count() }}</div></div>
            <div class="card"><div class="muted">Chamados</div><div class="metric">{{ $tickets->count() }}</div></div>

            <div class="card span-2">
                <h2>Dados da instância</h2>
                <p><strong>Cliente:</strong> {{ $companyName ?? '-' }}</p>
                <p><strong>Status:</strong> <span class="pill">{{ $company->status ?? 'Ativo' }}</span></p>
                <p><strong>Domínio principal:</strong> {{ $companyDomain ?? '-' }}</p>
                <p><strong>Tipo de instância:</strong> {{ $companyInstanceType ?? '-' }}</p>
            </div>

            <div class="card span-2">
                <h2>Abrir chamado</h2>
                <form method="POST" action="/cliente/chamados">
                    @csrf
                    <label>Título</label>
                    <input name="title" required placeholder="Ex.: Ajuste no módulo Guia Comercial">

                    <label>Prioridade</label>
                    <select name="priority">
                        <option value="Baixa">Baixa</option>
                        <option value="Média" selected>Média</option>
                        <option value="Alta">Alta</option>
                        <option value="Urgente">Urgente</option>
                    </select>

                    <label>Descrição</label>
                    <textarea name="description" placeholder="Descreva a solicitação, dúvida ou problema."></textarea>

                    <button type="submit">Abrir chamado</button>
                </form>
                <p class="muted">Esta área é consultiva. Alterações contratuais ou financeiras devem ser validadas pela equipe operacional.</p>
            </div>

            <div class="card span-4">
                <h2>Módulos contratados</h2>
                <table>
                    <thead><tr><th>Módulo</th><th>Produto</th><th>Plano</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($modules as $item)
                        <tr>
                            <td>{{ $item->module->name ?? $item->module->nome ?? '-' }}</td>
                            <td>{{ $item->plan->product->name ?? $item->plan->product->nome ?? '-' }}</td>
                            <td>{{ $item->plan->name ?? $item->plan->nome ?? '-' }}</td>
                            <td><span class="pill">{{ $item->status ?? 'Ativo' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Nenhum módulo vinculado ainda.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card span-4">
                <h2>Meus chamados</h2>
                <table>
                    <thead><tr><th>#</th><th>Título</th><th>Prioridade</th><th>Status</th><th>Abertura</th></tr></thead>
                    <tbody>
                    @forelse($tickets as $ticket)
                        <tr>
                            <td>{{ $ticket->id }}</td>
                            <td>{{ $ticket->title }}</td>
                            <td><span class="pill">{{ $ticket->priority }}</span></td>
                            <td><span class="pill">{{ $ticket->status }}</span></td>
                            <td>{{ optional($ticket->created_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Nenhum chamado aberto ainda.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card span-4">
                <h2>Cobranças</h2>
                <table>
                    <thead><tr><th>Descrição</th><th>Valor</th><th>Status</th><th>Vencimento</th></tr></thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->description ?? $payment->descricao ?? 'Cobrança' }}</td>
                            <td>R$ {{ number_format((float)($payment->amount ?? $payment->valor ?? $payment->value ?? 0), 2, ',', '.') }}</td>
                            <td><span class="pill">{{ $payment->status ?? '-' }}</span></td>
                            <td>{{ $payment->due_date ?? $payment->vencimento ?? $payment->data_vencimento ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Nenhuma cobrança cadastrada.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
