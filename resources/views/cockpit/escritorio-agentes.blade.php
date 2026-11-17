<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Escritório de Agentes IA — Cockpit HML</title>
<style>
:root{color-scheme:dark;font-family:Inter,system-ui,sans-serif;color:#eaf5ff;background:#081221;--line:#28475d;--muted:#a5bfd0;--accent:#55d5ed}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 60% 0%,#15384e,#081221 60%);min-height:100vh}
.shell{max-width:1380px;margin:auto;padding:24px}.top{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:26px}.title{font-size:23px;margin:0}.sub{font-size:12px;color:var(--muted);margin-top:5px}.back{color:#bcefff;text-decoration:none;border:1px solid var(--line);border-radius:10px;padding:9px 14px}.warn{background:#1a2937;border:1px solid #736343;padding:10px 14px;color:#f5d49a;border-radius:10px;font-size:12px;margin-bottom:16px}
.tabs{display:flex;gap:8px;margin:12px 0 22px}.tabs button{border:1px solid var(--line);background:#10283b;color:#c9e6f2;border-radius:9px;padding:10px 20px;font-weight:bold;cursor:pointer}.tabs button[aria-selected=true]{background:#1b5972;border-color:#69d8ee}
.metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.metric,.panel{border:1px solid var(--line);border-radius:15px;background:#10283bc9}.metric{padding:18px}.metric strong{display:block;font-size:29px;color:#72e7fd}.metric span{font-size:12px;color:var(--muted)}
.cols{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(300px,1fr);gap:14px;margin-top:15px}.panel{padding:18px}.panel h2{font-size:16px;margin:0 0 14px}.rooms{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;min-height:330px;background:repeating-linear-gradient(0deg,#091c2a,#091c2a 30px,#25415b 31px);padding:12px;border:1px solid var(--line);border-radius:11px}
.room{background:#15364b;border:1px solid #3b6680;border-radius:10px;padding:12px;text-align:left;color:#d2f4ff;cursor:pointer}.room:first-child{grid-column:span 2}.room b{display:block;margin:8px 0}.room small,.muted{color:var(--muted);font-size:12px}.room:focus-visible{outline:2px solid var(--accent)}.room .icon{font-size:24px}
input{width:100%;background:#0a1b29;border:1px solid var(--line);padding:11px;border-radius:10px;color:#fff;font:inherit}.agent{border:1px solid var(--line);padding:11px;margin-top:9px;border-radius:10px}.agent strong{display:block}.agent small{color:var(--muted)}.badge{font-size:10px;color:#e9be78;display:block;margin-top:7px}
.lower{display:grid;grid-template-columns:1.5fr 1fr;gap:14px;margin-top:15px}.kanban{display:grid;grid-template-columns:repeat(3,1fr);gap:9px}.lane{background:#0b1d2c;border:1px solid var(--line);border-radius:9px;padding:13px;min-height:110px}.lane b{font-size:12px}.lane p{font-size:11px;color:var(--muted)}
.skills{display:flex;gap:8px;flex-wrap:wrap}.skills span{background:#174159;color:#c3f0ff;border-radius:7px;padding:7px 9px;font-size:11px}.foot{color:var(--muted);font-size:11px;margin:20px 0}
@media(max-width:850px){.cols,.lower{grid-template-columns:1fr}.metrics{grid-template-columns:repeat(2,1fr)}}@media(max-width:460px){.shell{padding:13px}.rooms{grid-template-columns:repeat(2,1fr)}.kanban{grid-template-columns:1fr}}
</style>
</head>
<body><div class="shell">
<header class="top"><div><h1 class="title">🏢 Escritório de Agentes IA <small class="muted">M1 · HML</small></h1><p class="sub">Vitrine IA Pro · Equipes, departamentos, agentes e habilidades</p></div><a class="back" href="{{ route('cockpit.index') }}">← Voltar ao Cockpit</a></header>
<div class="warn"><strong>MVP HML.</strong> O painel registra missões e histórico real no banco. n8n, Avello e VIA ainda não estão conectados ao fluxo de execução.</div>
<div class="tabs" role="tablist" aria-label="Ambiente"><button type="button" role="tab" id="tab-DEV" aria-selected="true" data-scope="DEV">HUB DEV</button><button type="button" role="tab" id="tab-ECO" aria-selected="false" data-scope="ECO">HUB ECO</button></div>
<main>
<section class="metrics"><div class="metric"><strong id="agent-count">0</strong><span>Definições identificadas</span></div><div class="metric"><strong id="dept-count">0</strong><span>Departamentos propostos</span></div><div class="metric"><strong id="skill-count">0</strong><span>Habilidades candidatas</span></div><div class="metric"><strong>0</strong><span>Execuções online verificadas</span></div></section>
<div class="cols"><section class="panel"><h2>Planta conceitual · <span id="current-scope">DEV</span></h2><div class="rooms" id="rooms"></div></section><section class="panel"><h2>Agentes identificados em código</h2><input type="search" id="filter" aria-label="Buscar agente" placeholder="Pesquisar agente, produto ou função..." autocomplete="off"><div id="agents"></div></section></div>
@if(session('status'))
<div class="warn" role="status">{{ session('status') }}</div>
@endif
@if($errors->any())
<div class="warn" role="alert">{{ $errors->first() }}</div>
@endif
<section class="panel" style="margin-top:15px">
<h2>Nova missão</h2>
<form method="POST" action="{{ route('cockpit.escritorio-agentes.missoes.store') }}" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px">
@csrf
<input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
<label>Hub
<select name="domain" required><option value="DEV">DEV — Desenvolvimento</option><option value="ECO">ECO — Operação</option></select></label>
<label>Prioridade
<select name="priority" required><option value="normal">Normal</option><option value="high">Alta</option><option value="low">Baixa</option></select></label>
<label style="grid-column:1/-1">Título
<input name="title" minlength="4" maxlength="180" required value="{{ old('title') }}" placeholder="Ex.: Corrigir fluxo de cadastro"></label>
<label style="grid-column:1/-1">Descrição e resultado esperado
<textarea name="description" minlength="8" maxlength="5000" rows="3" required placeholder="Descreva o que precisa ser feito e como validar">{{ old('description') }}</textarea></label>
<div style="grid-column:1/-1"><button type="submit">Registrar missão</button><small class="muted">Registra no histórico. Não dispara execução automática.</small></div>
</form>
</section>
<section class="panel" style="margin-top:15px">
<h2>Histórico de missões</h2>
<div class="kanban">
<div class="lane"><b>Planejamento</b><strong>{{ $counts['planned'] }}</strong><p>Registradas e aguardando integração do executor.</p></div>
<div class="lane"><b>Em execução</b><strong>{{ $counts['executing'] }}</strong><p>Somente quando houver executor real.</p></div>
<div class="lane"><b>Aguardando VIA</b><strong>{{ $counts['awaiting'] }}</strong><p>Dependem de evidência e supervisão.</p></div>
</div>
<div style="overflow-x:auto;margin-top:14px">
<table style="width:100%;border-collapse:collapse;font-size:13px">
<thead><tr><th style="text-align:left;padding:9px">Missão</th><th>Hub</th><th>Status</th><th>Prioridade</th><th>Criada em</th></tr></thead>
<tbody>
@forelse($missions as $mission)
<tr style="border-top:1px solid var(--line)">
<td style="padding:10px"><strong>{{ $mission->title }}</strong><br><small class="muted">{{ \Illuminate\Support\Str::limit($mission->description, 100) }}</small></td>
<td>{{ $mission->domain }}</td><td>{{ $mission->status }}</td><td>{{ $mission->priority }}</td><td>{{ $mission->created_at?->format('d/m/Y H:i') }}</td>
</tr>
@empty
<tr><td colspan="5" class="muted" style="padding:14px">Nenhuma missão registrada ainda.</td></tr>
@endforelse
</tbody></table>
</div>
</section>

<div class="lower"><section class="panel"><h2>Missões e tarefas</h2><div class="kanban"><div class="lane"><b>Planejamento</b><p>Sem fonte operacional conectada.</p></div><div class="lane"><b>Em execução</b><p>Sem telemetria integrada.</p></div><div class="lane"><b>Aguardando VIA</b><p>Supervisão ainda não conectada.</p></div></div></section><section class="panel"><h2>Habilidades candidatas</h2><div id="skills" class="skills"></div></section></div>
</main><p class="foot">Fonte: app/Models/AiAgent.php e database/seeders/AiAgentSeeder.php · Código consultado · Ambiente HML · Nenhuma chamada paga ou mutação.</p>
</div>
<script>
'use strict';
const inventory={
DEV:{departments:[['Diretoria técnica','🧭','dev.plan'],['Arquitetura','🏗️','dev.architecture.review'],['Desenvolvimento','💻','dev.debug'],['Qualidade','🧪','dev.tests'],['Integrações','🔗','dev.api.validate']],agents:[],skills:['dev.plan','dev.architecture.review','dev.debug','dev.implement','dev.tests','dev.diff.review','dev.api.validate']},
ECO:{departments:[['Diretoria operacional','🧭','eco.intake'],['Marketing','🎯','eco.copy'],['Editorial / TV','🎬','eco.editorial.verify'],['Produtos','🧩','eco.product.qa'],['Atendimento','🎧','eco.support.triage'],['Automação','⚙️','eco.workflow.plan']],agents:[
['Editorial IA','Portal News AI / TV Digital','Reescrita editorial, SEO e categorização'],
['Repórter IA','TV Digital / White Label','Roteiros e vídeos institucionais'],
['Comercial IA','Centro Comercial','Atendimento, diagnóstico e propostas'],
['Marketing IA','Marketing','Campanhas, anúncios e calendário'],
['Turismo IA','Visite Cidade','Roteiros e informações turísticas'],
['Compras IA','Compras Públicas','DFD, ETP, TR e documentos'],
['Município IA','Município Digital IA','FAQ e comunicação institucional'],
['Cursos IA','Cursos IA','Cursos, módulos e aulas'],
['SISMED IA','SISMED','Apoio administrativo e protocolos']
],skills:['eco.intake','eco.marketing.plan','eco.copy','eco.research','eco.editorial.verify','eco.video.script','eco.product.qa','eco.support.triage','eco.workflow.plan']}}
let scope='DEV';
const esc=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
function render(){const d=inventory[scope],query=document.getElementById('filter').value.trim().toLocaleLowerCase('pt-BR');document.getElementById('current-scope').textContent=scope;document.getElementById('agent-count').textContent=d.agents.length;document.getElementById('dept-count').textContent=d.departments.length;document.getElementById('skill-count').textContent=d.skills.length;document.getElementById('rooms').innerHTML=d.departments.map(a=>'<button type="button" class="room" title="Proposta: sem estado online"><span class="icon">'+a[1]+'</span><b>'+esc(a[0])+'</b><small>'+esc(a[2])+' · status desconhecido</small></button>').join('');const list=d.agents.filter(a=>a.join(' ').toLocaleLowerCase('pt-BR').includes(query));document.getElementById('agents').innerHTML=list.length?list.map(a=>'<div class="agent"><strong>'+esc(a[0])+'</strong><small>'+esc(a[1])+' · '+esc(a[2])+'</small><span class="badge">Definição em código · runtime não verificado</span></div>').join(''):'<p class="muted">'+(scope==='DEV'?'Nenhum agente DEV inventariado nesta fonte.':'Nenhum agente corresponde à pesquisa.')+'</p>';document.getElementById('skills').innerHTML=d.skills.map(s=>'<span>'+esc(s)+'</span>').join('')}
document.querySelectorAll('[data-scope]').forEach(b=>b.addEventListener('click',()=>{scope=b.dataset.scope;document.getElementById('filter').value='';document.querySelectorAll('[data-scope]').forEach(x=>x.setAttribute('aria-selected',String(x===b)));render()}));document.getElementById('filter').addEventListener('input',render);render();
</script></body></html>
