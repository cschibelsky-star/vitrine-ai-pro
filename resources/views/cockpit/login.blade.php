<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cockpit Administrativo | Vitrine IA Pro</title>
    <style>
        :root{color-scheme:dark;--bg:#0B1020;--card:#151B2E;--border:#26304A;--text:#F8FAFC;--muted:#94A3B8;--accent:#6366F1;--error:#EF4444}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:radial-gradient(circle at top,#17203a 0,var(--bg) 45%);font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--text);padding:24px}
        .shell{width:min(440px,100%)}.brand{margin-bottom:24px}.brand small{display:block;color:var(--muted);text-transform:uppercase;letter-spacing:.16em;font-weight:700;margin-bottom:8px}.brand h1{font-size:30px;line-height:1.15;margin:0}.brand p{color:var(--muted);line-height:1.6;margin:10px 0 0}
        .card{background:rgba(21,27,46,.94);border:1px solid var(--border);border-radius:16px;padding:28px;box-shadow:0 24px 70px rgba(0,0,0,.35)}label{display:block;font-size:14px;font-weight:700;margin:0 0 8px}input[type=email],input[type=password]{width:100%;background:#0f1527;border:1px solid var(--border);border-radius:10px;color:var(--text);padding:13px 14px;font-size:15px;outline:none}input:focus{border-color:#64748b;box-shadow:0 0 0 3px rgba(99,102,241,.14)}.field{margin-bottom:18px}.remember{display:flex;gap:9px;align-items:center;color:var(--muted);font-size:14px;margin:2px 0 20px}.remember input{accent-color:var(--accent)}button{width:100%;border:0;border-radius:10px;padding:13px 16px;background:var(--text);color:#0B1020;font-size:15px;font-weight:800;cursor:pointer}.error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.35);color:#fecaca;border-radius:10px;padding:11px 13px;margin-bottom:18px;font-size:14px}.foot{text-align:center;color:var(--muted);font-size:12px;margin-top:18px}
    </style>
</head>
<body>
<main class="shell">
    <header class="brand">
        <small>Vitrine IA Pro</small>
        <h1>Cockpit Administrativo</h1>
        <p>Acesso centralizado aos ambientes administrativos autorizados.</p>
    </header>
    <section class="card">
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif
        @if (session('status'))
            <div class="error" style="border-color:rgba(34,197,94,.35);background:rgba(34,197,94,.1);color:#bbf7d0">{{ session('status') }}</div>
        @endif

        @if ($recoveryMode ?? false)
            <form method="POST" action="{{ route('cockpit.password.email') }}">
                @csrf
                <div class="field">
                    <label for="email">E-mail administrativo</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>
                <button type="submit">Solicitar redefinicao</button>
                <p class="foot"><a href="{{ route('cockpit.login') }}" style="color:var(--text)">Voltar para o login</a></p>
            </form>
        @else
            <form method="POST" action="{{ route('cockpit.login.submit') }}">
                @csrf
                <div class="field">
                    <label for="email">E-mail</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Senha</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <label class="remember"><input type="checkbox" name="remember" value="1"> Manter sessao neste dispositivo</label>
                <button type="submit">Entrar no Cockpit</button>
                <p class="foot"><a href="{{ route('cockpit.password.request') }}" style="color:var(--text)">Esqueci minha senha</a></p>
            </form>
        @endif
    </section>
    <div class="foot">Ambiente de homologacao · acesso restrito</div>
</main>
</body>
</html>
