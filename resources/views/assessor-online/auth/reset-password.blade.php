<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Definir senha | Assessor Online</title></head>
<body><main><h1>Definir nova senha</h1>
@if($errors->any()) <p role="alert">{{ $errors->first() }}</p> @endif
<form method="POST" action="{{ route('assessor.password.update') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<label for="email">E-mail</label><input id="email" name="email" type="email" required value="{{ old('email', $email) }}">
<label for="password">Nova senha (mínimo 16 caracteres)</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="16" required>
<label for="password_confirmation">Confirmar senha</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="16" required>
<label id="password-visibility" hidden><input id="show-passwords" type="checkbox" aria-controls="password password_confirmation"> Mostrar senhas</label>
<button type="submit">Salvar senha</button></form></main>
<script>
    const visibility = document.getElementById('show-passwords');
    const passwords = document.querySelectorAll('#password, #password_confirmation');

    visibility.addEventListener('change', () => {
        for (const input of passwords) {
            input.type = visibility.checked ? 'text' : 'password';
        }
    });
    document.getElementById('password-visibility').hidden = false;
</script>
</body></html>
