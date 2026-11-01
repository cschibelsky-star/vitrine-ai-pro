<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar | Assessor Online</title>
    <style>
        :root { color-scheme: dark; font-family: Inter, system-ui, sans-serif; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; background: radial-gradient(ellipse at 20% 10%, #163858, #081321 65%); color: #f4f8ff; }
        main { width: min(400px, calc(100% - 48px)); padding: 36px; background: #101e30; border: 1px solid #2d4a67; border-radius: 20px; box-shadow: 0 24px 80px #0006; }
        h1 { margin: 0 0 8px; font-size: 29px; }
        p { color: #b2c4d8; line-height: 1.5; }
        label { display: block; margin: 18px 0 7px; font-weight: 600; }
        input { box-sizing: border-box; width: 100%; padding: 13px; border-radius: 9px; background: #091626; border: 1px solid #42617c; color: white; font: inherit; }
        button { width: 100%; padding: 14px; margin-top: 24px; border: 0; border-radius: 9px; background: #37bdf5; color: #061521; font-weight: 750; cursor: pointer; }
        .error { color: #ffc2c2; }
        small { color: #9fb6cf; }
    </style>
</head>
<body>
<main>
    <small>VITRINE IA PRO · SOLUÇÕES PARA GABINETES</small>
    <h1>Assessor Online</h1>
    <p>Entre com as credenciais autorizadas do seu gabinete.</p>
    @if ($errors->any())
        <p class="error" role="alert">{{ $errors->first() }}</p>
    @endif
    <form method="POST" action="{{ route('assessor.login.submit') }}">
        @csrf
        <label for="email">E-mail</label>
        <input id="email" name="email" type="email" autocomplete="username" required value="{{ old('email') }}">
        <label for="password">Senha</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <button type="submit">Entrar no gabinete</button>
    </form>
</main>
</body>
</html>
