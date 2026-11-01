<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Recuperar acesso | Assessor Online</title></head>
<body><main><h1>Recuperar acesso</h1><p>Informe seu e-mail cadastrado no gabinete.</p>
@if(session('status')) <p role="status">{{ session('status') }}</p> @endif
@if($errors->any()) <p role="alert">{{ $errors->first() }}</p> @endif
<form method="POST" action="{{ route('assessor.password.email') }}">@csrf
<label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}">
<button type="submit">Enviar instruções</button></form>
<a href="{{ route('assessor.login') }}">Voltar ao login</a></main></body></html>
