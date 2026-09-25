<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vitrine IA Pro App</title>
    <meta name="theme-color" content="#0B1020">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Vitrine IA Pro">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/pwa/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/pwa/icon.svg">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#0B1020;color:#F8FAFC;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.shell{max-width:1440px;margin:0 auto;padding:32px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:20px}.eyebrow{color:#94A3B8;font-size:12px;text-transform:uppercase;letter-spacing:.12em}.title{font-size:30px;font-weight:750;margin:5px 0 0}.user{color:#94A3B8;font-size:14px}.quickbar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:28px}.quicklink{display:inline-flex;text-decoration:none;color:#F8FAFC;border:1px solid #26304A;background:#151B2E;padding:10px 14px;border-radius:10px;font-weight:650;font-size:13px}.grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.card{display:flex;flex-direction:column;min-height:220px;padding:22px;background:#151B2E;border:1px solid #26304A;border-radius:16px}.meta{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:18px}.category,.stage{font-size:12px;color:#94A3B8}.stage{padding:5px 9px;border:1px solid #26304A;border-radius:999px;text-transform:uppercase}.card h2{font-size:19px;margin:0 0 9px}.card p{color:#94A3B8;font-size:14px;line-height:1.55;margin:0 0 16px}.facts{display:grid;gap:7px;margin:0 0 18px;font-size:12px}.fact{display:flex;justify-content:space-between;gap:14px;border-top:1px solid #26304A;padding-top:7px}.fact span:first-child{color:#64748B}.fact span:last-child{color:#CBD5E1;text-align:right;word-break:break-word}.actions{margin-top:auto}.button{display:inline-flex;text-decoration:none;color:#F8FAFC;border:1px solid #26304A;padding:10px 14px;border-radius:10px;font-weight:650;font-size:13px}.button.disabled{opacity:.45;cursor:not-allowed}.empty{color:#94A3B8}.mobilebar{display:none}@media(max-width:1100px){.grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:820px){.shell{padding:24px}.grid{grid-template-columns:repeat(2,minmax(0,1fr))}.card{min-height:200px}}@media(max-width:640px){body{padding-bottom:calc(76px + env(safe-area-inset-bottom))}.shell{padding:18px 16px 28px}.topbar{align-items:flex-start;gap:8px;margin-bottom:16px}.title{font-size:24px}.user{font-size:12px}.quickbar{margin-bottom:18px}.quicklink{min-height:44px;align-items:center}.grid{grid-template-columns:1fr;gap:12px}.card{min-height:0;padding:18px;border-radius:14px}.meta{margin-bottom:12px}.fact{align-items:flex-start}.button{min-height:44px;align-items:center;justify-content:center;width:100%}.mobilebar{position:fixed;left:10px;right:10px;bottom:calc(10px + env(safe-area-inset-bottom));z-index:50;display:grid;grid-template-columns:repeat(3,1fr);gap:6px;padding:7px;border:1px solid #26304A;border-radius:16px;background:rgba(11,16,32,.94);backdrop-filter:blur(18px);box-shadow:0 18px 50px rgba(0,0,0,.4)}.mobilebar a{min-height:48px;display:flex;align-items:center;justify-content:center;text-align:center;text-decoration:none;color:#CBD5E1;font-size:11px;font-weight:700;border-radius:11px}.mobilebar a.active{background:#151B2E;color:#fff}}
    </style>
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div><div class="eyebrow">Vitrine IA Pro · {{ strtoupper(config('cockpit.environment', 'hml')) }}</div><h1 class="title">Cockpit Administrativo</h1></div>
        <div class="user">{{ auth()->user()->name }}</div>
    </header>

    <nav class="quickbar" aria-label="Atalhos do Cockpit">
        <a class="quicklink" href="{{ route('cockpit.webmail') }}">Comunicação · Webmail</a>
    </nav>

    @if($applications->isEmpty())
        <p class="empty">Nenhuma aplicacao autorizada para este usuario.</p>
    @else
        <main id="apps" class="grid">
            @foreach($applications as $app)
                <article class="card">
                    <div class="meta"><span class="category">{{ $app['category'] }}</span><span class="stage">{{ $app['stage'] }}</span></div>
                    <h2>{{ $app['name'] }}</h2>
                    <p>{{ $app['description'] }}</p>
                    <div class="facts">
                        <div class="fact"><span>Versao</span><span>{{ $app['version'] ?? 'Nao informada' }}</span></div>
                        <div class="fact"><span>Branch / build</span><span>{{ $app['branch'] ?? 'Nao aplicavel' }}</span></div>
                        <div class="fact"><span>Ambiente</span><span>{{ strtoupper($app['stage'] ?? 'n/d') }}</span></div>
                        <div class="fact"><span>Status</span><span>{{ str_replace('_', ' ', $app['status'] ?? 'unknown') }}</span></div>
                        <div class="fact"><span>Health</span><span>{{ str_replace('_', ' ', $app['health'] ?? 'unknown') }}</span></div>
                        <div class="fact"><span>Integracao</span><span>{{ str_replace('_', ' ', $app['integration'] ?? 'pending') }}</span></div>
                    </div>
                    <div class="actions">
                        @if(!empty($app['sso']))
                            <a class="button" href="{{ route('cockpit.open', ['slug' => $app['slug']]) }}">Abrir com SSO</a>
                        @elseif(!empty($app['admin_url']))
                            <a class="button" href="{{ $app['admin_url'] }}" rel="noopener">Abrir Administracao</a>
                        @else
                            <span class="button disabled">Integracao pendente</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </main>
    @endif
    <nav class="mobilebar" aria-label="Navegacao mobile do Cockpit">
        <a class="active" href="{{ route('cockpit.index') }}">Inicio</a>
        <a href="#apps">Apps</a>
        <a href="{{ route('cockpit.webmail') }}">Webmail</a>
    </nav>
</div>
<script>
if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => navigator.serviceWorker.register("/sw.js").catch(() => {}));
}
</script>
</body>
</html>