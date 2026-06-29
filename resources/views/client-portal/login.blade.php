<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Login do Cliente — Vitrine AI Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:Arial,sans-serif; background:linear-gradient(135deg,#0f172a,#1d4ed8,#06b6d4); color:#0f172a; }
        .box { width:100%; max-width:420px; background:#fff; border-radius:22px; padding:30px; box-shadow:0 24px 70px rgba(15,23,42,.35); }
        .brand { font-size:14px; text-transform:uppercase; color:#2563eb; font-weight:800; letter-spacing:.08em; }
        h1 { margin:8px 0 4px; font-size:28px; }
        p { margin:0 0 22px; color:#64748b; }
        label { display:block; margin:14px 0 6px; font-weight:700; font-size:14px; }
        input { width:100%; box-sizing:border-box; padding:13px 14px; border:1px solid #cbd5e1; border-radius:12px; font-size:15px; }
        button { width:100%; margin-top:20px; padding:14px; border:0; border-radius:12px; background:#2563eb; color:#fff; font-size:16px; font-weight:800; cursor:pointer; }
        .error { background:#fef2f2; color:#b91c1c; padding:10px 12px; border-radius:12px; margin:14px 0; font-size:14px; }
        .remember { display:flex; gap:8px; align-items:center; margin-top:12px; color:#475569; font-size:14px; }
        .remember input { width:auto; }
        .foot { margin-top:16px; font-size:12px; color:#64748b; text-align:center; }
    </style>
</head>
<body>
    <form class="box" method="POST" action="/cliente/login">
        @csrf
        <div class="brand">Vitrine AI Pro</div>
        <h1>Área do Cliente</h1>
        <p>Acesse o resumo do seu projeto, módulos, contratos e cobranças.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>

        <label for="password">Senha</label>
        <input id="password" name="password" type="password" required>

        <label class="remember">
            <input type="checkbox" name="remember" value="1">
            Lembrar acesso
        </label>

        <button type="submit">Entrar</button>

        <div class="foot">Centro Operacional Master — Portal do Cliente</div>
    </form>
</body>
</html>
