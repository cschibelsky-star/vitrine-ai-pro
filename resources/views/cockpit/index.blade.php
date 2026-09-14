<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cockpit Administrativo | Vitrine IA Pro</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#0B1020;color:#F8FAFC;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.shell{max-width:1440px;margin:0 auto;padding:32px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:32px}.eyebrow{color:#94A3B8;font-size:12px;text-transform:uppercase;letter-spacing:.12em}.title{font-size:30px;font-weight:750;margin:5px 0 0}.user{color:#94A3B8;font-size:14px}.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.card{display:flex;flex-direction:column;min-height:220px;padding:22px;background:#151B2E;border:1px solid #26304A;border-radius:16px}.meta{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:18px}.category,.stage{font-size:12px;color:#94A3B8}.stage{padding:5px 9px;border:1px solid #26304A;border-radius:999px;text-transform:uppercase}.card h2{font-size:19px;margin:0 0 9px}.card p{color:#94A3B8;font-size:14px;line-height:1.55;margin:0 0 22px}.actions{margin-top:auto}.button{display:inline-flex;text-decoration:none;color:#F8FAFC;border:1px solid #26304A;padding:10px 14px;border-radius:10px;font-weight:650;font-size:13px}.button.disabled{opacity:.45;cursor:not-allowed}.empty{color:#94A3B8}@media(max-width:1100px){.grid{grid-template-columns:repeat(3,1fr)}}@media(max-width:820px){.grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:560px){.shell{padding:22px}.topbar{align-items:flex-start;flex-direction:column}.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div><div class="eyebrow">Vitrine IA Pro · {{ strtoupper(config('cockpit.environment', 'hml')) }}</div><h1 class="title">Cockpit Administrativo</h1></div>
        <div class="user">{{ auth()->user()->name }}</div>
    </header>

    @if($applications->isEmpty())
        <p class="empty">Nenhuma aplicacao autorizada para este usuario.</p>
    @else
        <main class="grid">
            @foreach($applications as $app)
                <article class="card">
                    <div class="meta"><span class="category">{{ $app['category'] }}</span><span class="stage">{{ $app['stage'] }}</span></div>
                    <h2>{{ $app['name'] }}</h2>
                    <p>{{ $app['description'] }}</p>
                    <div class="actions">
                        @if(!empty($app['admin_url']))
                            <a class="button" href="{{ $app['admin_url'] }}" rel="noopener">Abrir Administracao</a>
                        @else
                            <span class="button disabled">Integracao pendente</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </main>
    @endif
</div>
</body>
</html>
