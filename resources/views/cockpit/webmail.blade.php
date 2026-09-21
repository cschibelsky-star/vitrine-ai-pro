<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Webmail | Cockpit Vitrine IA Pro</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#0B1020;color:#F8FAFC;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.shell{max-width:1500px;margin:auto;padding:28px}.top{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:18px}.eyebrow{font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#94A3B8}.title{font-size:30px;margin:4px 0}.btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:10px;border:1px solid #334155;background:#111827;color:#fff;text-decoration:none;font-weight:700;font-size:13px;cursor:pointer}.btn.primary{background:#2563EB;border-color:#2563EB}.layout{display:grid;grid-template-columns:250px minmax(340px,420px) 1fr;gap:16px}.panel{background:#151B2E;border:1px solid #26304A;border-radius:16px;padding:16px;min-height:650px}.nav{display:grid;gap:7px}.nav a{color:#CBD5E1;text-decoration:none;padding:10px 12px;border-radius:9px}.nav a.active{background:#202942;color:#fff}.account{margin-top:18px;padding-top:14px;border-top:1px solid #26304A}.muted{color:#94A3B8;font-size:13px}.status{display:inline-flex;margin-top:8px;padding:5px 8px;border-radius:999px;border:1px solid #475569;font-size:11px;text-transform:uppercase}.notice,.error,.success{padding:12px 14px;border-radius:10px;margin-bottom:14px}.notice{background:#422006;border:1px solid #854D0E;color:#FDE68A}.error{background:#3f1515;border:1px solid #7f1d1d;color:#fecaca}.success{background:#123020;border:1px solid #166534;color:#bbf7d0}.toolbar{display:flex;gap:8px;align-items:center;justify-content:space-between;margin-bottom:12px}.messages{display:grid;gap:8px;max-height:590px;overflow:auto}.message{display:block;padding:12px;border:1px solid #26304A;border-radius:12px;background:#11182A;color:#fff;text-decoration:none}.message:hover{border-color:#475569}.message.unread{border-left:3px solid #60A5FA}.message strong{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.meta{font-size:12px;color:#94A3B8;margin-top:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.reader{min-height:500px}.reader h2{margin-top:6px}.reader pre{white-space:pre-wrap;font:inherit;line-height:1.5;color:#E2E8F0}.compose{margin-top:18px;border-top:1px solid #26304A;padding-top:16px}.field{display:grid;gap:6px;margin-bottom:10px}.field input,.field textarea{width:100%;background:#0F172A;border:1px solid #334155;color:#fff;border-radius:9px;padding:10px}.field textarea{min-height:140px;resize:vertical}.row{display:flex;gap:8px;flex-wrap:wrap}@media(max-width:1050px){.layout{grid-template-columns:220px 1fr}.reader{grid-column:1/-1}.panel{min-height:auto}}@media(max-width:700px){.layout{grid-template-columns:1fr}.shell{padding:16px}.top{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<div class="shell">
<header class="top">
<div><div class="eyebrow">Cockpit · Comunicação</div><h1 class="title">Webmail Vitrine IA Pro</h1><div class="muted">{{ $accounts[$accountKey]['address'] ?? '' }}</div></div>
<div class="row"><a class="btn" href="{{ route('cockpit.index') }}">Voltar ao Cockpit</a><a class="btn" href="{{ route('cockpit.webmail.probe',['account'=>$accountKey]) }}" target="_blank">Testar conexão</a></div>
</header>

@if(!$enabled)<div class="notice">Webmail desativado no runtime.</div>@endif
@if($connectionError)<div class="error">Falha de conexão: {{ $connectionError }}</div>@endif
@if(session('status'))<div class="success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif

<div class="layout">
<aside class="panel">
<div class="eyebrow">Pastas</div>
<nav class="nav" style="margin-top:10px">
@foreach($folders as $folderName)
<a class="{{ $folder === $folderName ? 'active' : '' }}" href="{{ route('cockpit.webmail',['account'=>$accountKey,'folder'=>$folderName]) }}">{{ $folderName }}</a>
@endforeach
</nav>
@foreach($accounts as $key=>$account)
<section class="account">
<a href="{{ route('cockpit.webmail',['account'=>$key]) }}" style="color:#fff;text-decoration:none;font-weight:700">{{ $account['label'] ?? $key }}</a>
<div class="muted">{{ $account['address'] ?: 'Não configurado' }}</div>
<span class="status">{{ $enabled && !empty($account['address']) ? 'Ativo' : 'Pendente' }}</span>
</section>
@endforeach
</aside>

<section class="panel">
<div class="toolbar"><div><div class="eyebrow">Mensagens</div><strong>{{ $folder }}</strong></div><a class="btn" href="{{ route('cockpit.webmail',['account'=>$accountKey,'folder'=>$folder]) }}">Atualizar</a></div>
<div class="messages">
@forelse($messages as $mail)
<a class="message {{ empty($mail['seen']) ? 'unread' : '' }}" href="#" data-message-url="{{ route('cockpit.webmail.message',['uid'=>$mail['uid'],'account'=>$accountKey,'folder'=>$folder]) }}">
<strong>{{ $mail['subject'] }}</strong>
<div class="meta">{{ $mail['from'] }}</div>
<div class="meta">{{ $mail['date'] }}</div>
</a>
@empty
<div class="muted">Nenhuma mensagem encontrada nesta pasta.</div>
@endforelse
</div>
</section>

<section class="panel reader">
<div id="reader-empty"><div class="eyebrow">Leitura</div><h2>Selecione uma mensagem</h2><p class="muted">O conteúdo aparecerá aqui sem sair do Cockpit.</p></div>
<div id="reader-content" hidden>
<div class="eyebrow">Mensagem</div><h2 id="mail-subject"></h2>
<div class="muted" id="mail-meta"></div>
<pre id="mail-body"></pre>
</div>

<div class="compose">
<div class="eyebrow">Nova mensagem</div>
<form method="post" action="{{ route('cockpit.webmail.send') }}" style="margin-top:12px">
@csrf
<input type="hidden" name="account" value="{{ $accountKey }}">
<div class="field"><label>Para</label><input type="email" name="to" required value="{{ old('to') }}"></div>
<div class="field"><label>Assunto</label><input type="text" name="subject" maxlength="180" required value="{{ old('subject') }}"></div>
<div class="field"><label>Mensagem</label><textarea name="body" required>{{ old('body') }}</textarea></div>
<button class="btn primary" type="submit">Enviar pelo Titan</button>
</form>
</div>
</section>
</div>
</div>
<script>
document.querySelectorAll('[data-message-url]').forEach(function(link){
  link.addEventListener('click',async function(e){
    e.preventDefault();
    const empty=document.getElementById('reader-empty');
    const content=document.getElementById('reader-content');
    try{
      const response=await fetch(link.dataset.messageUrl,{headers:{'Accept':'application/json'}});
      const data=await response.json();
      if(!response.ok||!data.ok) throw new Error(data.error||'Falha ao carregar mensagem');
      document.getElementById('mail-subject').textContent=data.message.subject||'(Sem assunto)';
      document.getElementById('mail-meta').textContent=(data.message.from||'')+' · '+(data.message.date||'');
      document.getElementById('mail-body').textContent=data.message.body||'';
      empty.hidden=true;content.hidden=false;
    }catch(err){alert(err.message);}
  });
});
</script>
</body>
</html>
