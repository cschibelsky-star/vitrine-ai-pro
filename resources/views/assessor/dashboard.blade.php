<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $cabinetName }} | Assessor Online</title>
<style>
:root{font-family:Inter,system-ui,sans-serif;color:#eaf4ff;background:#081326}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at top right,#12395a,#081326 65%);min-height:100vh}
header{padding:28px max(24px,calc((100vw - 1200px)/2));border-bottom:1px solid #284561;display:flex;justify-content:space-between;gap:12px;align-items:center}
.logo{font-weight:800;font-size:22px;letter-spacing:.3px}.sub{font-size:12px;color:#75d7e8}
main{max-width:1200px;margin:32px auto;padding:0 24px}h1{font-size:30px;margin:0 0 8px}p{color:#9db5ce}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin:28px 0}
.card,.panel{background:#10243a;border:1px solid #2b4763;border-radius:18px;padding:22px;box-shadow:0 16px 50px #020b1640}
.card strong{font-size:34px;display:block;margin-top:10px}.card span{color:#a7bfd6}
section{display:grid;grid-template-columns:1fr 1.6fr;gap:18px}h2{font-size:18px;margin:0 0 18px}
input,textarea,select{display:block;width:100%;background:#081a2c;border:1px solid #385571;color:#fff;border-radius:9px;padding:12px;margin:10px 0}
button{background:linear-gradient(90deg,#13bdd3,#367bf6);border:0;border-radius:9px;color:#fff;font-weight:700;padding:12px 18px;cursor:pointer}
table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:12px 8px;border-bottom:1px solid #29445d;font-size:13px}
a{color:#72dbf0}small{color:#9db5ce}.error{color:#ffb1b1}.success{color:#86edcf}
@media(max-width:800px){section,.grid{grid-template-columns:1fr}header{padding:20px 24px}}
</style>
</head>
<body>
<header><div><div class="logo">ASSESSOR ONLINE</div><div class="sub">Inteligência artificial para a gestão do mandato</div></div><small>Ambiente de desenvolvimento</small></header>
<main>
<h1>{{ $cabinetName }}</h1><p>Gestão de atendimentos e demandas do gabinete em tempo real.</p>
@php
$total = count($demands);
$open = count(array_filter($demands, fn($d) => $d->status === 'open'));
$progress = count(array_filter($demands, fn($d) => $d->status === 'in_progress'));
@endphp
<div class="grid"><div class="card"><span>Demandas registradas</span><strong>{{ $total }}</strong></div><div class="card"><span>Aguardando atendimento</span><strong>{{ $open }}</strong></div><div class="card"><span>Em andamento</span><strong>{{ $progress }}</strong></div></div>
<div class="grid">
<div class="card"><span>Cidadãos cadastrados</span><strong>{{ $metrics['citizens'] }}</strong></div>
<div class="card"><span>Demandas resolvidas</span><strong>{{ $metrics['demands_resolved'] }}</strong></div>
<div class="card"><span>Compromissos agendados</span><strong>{{ $metrics['appointments'] }}</strong></div>
</div>
<section>
<div class="panel"><h2>Nova demanda</h2>
<form id="demand-form"><label>Assunto<input name="subject" maxlength="200" required></label><label>Descrição<textarea name="description" maxlength="10000" rows="5" required></textarea></label><button type="submit">Registrar demanda</button></form><p id="feedback" role="status"></p>
<h2>Equipe</h2>@foreach($members as $member)<p>{{ $member->name }} <small>({{ $member->role }})</small></p>@endforeach
</div>
<div class="panel"><h2>Acompanhamento das demandas</h2><div style="overflow-x:auto"><table><thead><tr><th>Protocolo</th><th>Assunto</th><th>Responsável</th><th>Situação</th><th>Histórico</th></tr></thead><tbody>
@forelse($demands as $d)
<tr><td>#{{ $d->id }}</td><td>{{ $d->subject }}</td><td>
<select aria-label="Responsável pela demanda {{ $d->id }}" onchange="assignDemand({{ $d->id }},this.value)">
<option value="">Não atribuído</option>@foreach($members as $m)<option value="{{ $m->id }}" @selected($d->assigned_to == $m->id)>{{ $m->name }}</option>@endforeach
</select></td><td><select aria-label="Situação da demanda {{ $d->id }}" onchange="changeStatus({{ $d->id }},this.value)">
@foreach(['open'=>'Aberta','in_progress'=>'Em andamento','resolved'=>'Resolvida'] as $value=>$label)<option value="{{ $value }}" @selected($d->status === $value)>{{ $label }}</option>@endforeach
</select></td><td><button type="button" onclick="showHistory({{ $d->id }})">Consultar</button></td></tr>
@empty<tr><td colspan="5">Nenhuma demanda registrada.</td></tr>@endforelse
</tbody></table></div><div id="history-panel" aria-live="polite"></div></div></section>
<section style="margin-top:18px">
<div class="panel"><h2>Cadastro de cidadãos</h2>
<form id="citizen-form">
<input name="name" maxlength="160" placeholder="Nome completo" required>
<input name="phone" maxlength="32" placeholder="Telefone">
<input name="email" type="email" maxlength="180" placeholder="E-mail">
<input name="neighborhood" maxlength="120" placeholder="Bairro">
<button type="submit">Cadastrar cidadão</button></form>
<p id="citizen-feedback" role="status"></p>
<h2>Lista de cidadãos</h2>
@forelse($citizens as $citizen)<p>{{ $citizen->name }} <small>{{ $citizen->neighborhood }}</small></p>
@empty<p>Nenhum cidadão cadastrado.</p>@endforelse
</div>
<div class="panel"><h2>Agenda do gabinete</h2>
<form id="appointment-form">
<input name="title" maxlength="180" placeholder="Assunto do compromisso" required>
<label>Início<input type="datetime-local" name="starts_at" required></label>
<label>Fim<input type="datetime-local" name="ends_at" required></label>
<select name="citizen_id"><option value="">Cidadão (opcional)</option>
@foreach($citizens as $citizen)<option value="{{ $citizen->id }}">{{ $citizen->name }}</option>@endforeach
</select>
<select name="assessor_id"><option value="">Assessor (opcional)</option>
@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach
</select>
<button type="submit">Agendar compromisso</button></form>
<p id="appointment-feedback" role="status"></p>
@forelse($appointments as $appointment)<p><strong>{{ $appointment->title }}</strong><br><small>{{ $appointment->starts_at }} — {{ $appointment->ends_at }}</small></p>
@empty<p>Sem compromissos agendados.</p>@endforelse
</div></section>
<section style="margin-top:18px">
<div class="panel"><h2>Nova tarefa da equipe</h2>
<form id="task-form">
<input name="title" maxlength="200" placeholder="Título da tarefa" required>
<textarea name="description" maxlength="10000" placeholder="Orientações" rows="3"></textarea>
<label>Prazo<input type="date" name="due_date"></label>
<select name="assigned_to"><option value="">Sem responsável</option>
@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach
</select>
<select name="demand_id"><option value="">Sem demanda vinculada</option>
@foreach($demands as $demand)<option value="{{ $demand->id }}">#{{ $demand->id }} — {{ $demand->subject }}</option>@endforeach
</select>
<button type="submit">Criar tarefa</button></form><p id="task-feedback" role="status"></p>
</div>
<div class="panel"><h2>Atividades da equipe</h2>
@forelse($tasks as $task)
<div style="padding:12px 0;border-bottom:1px solid #29445d">
<strong>{{ $task->title }}</strong><br><small>Prazo: {{ $task->due_date ?? 'não definido' }}</small>
<select aria-label="Situação da tarefa {{ $task->id }}" onchange="changeTaskStatus({{ $task->id }},this.value)">
@foreach(['pending'=>'Pendente','in_progress'=>'Em andamento','completed'=>'Concluída','cancelled'=>'Cancelada'] as $value=>$label)
<option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
@endforeach
</select></div>
@empty<p>Sem tarefas cadastradas.</p>@endforelse
</div></section>
<div class="panel" style="margin-top:18px">
<h2>Inteligência operacional</h2>
<p>Recomendações automáticas baseadas nos indicadores do gabinete. Integração com o Centro IA em preparação.</p>
@forelse($insights['recommendations'] as $recommendation)
<div style="padding:12px 0;border-bottom:1px solid #29445d">
<strong>{{ $recommendation['title'] }}</strong>
<p>{{ $recommendation['reason'] }}</p>
</div>
@empty<p>Nenhuma pendência prioritária identificada pelas regras atuais.</p>@endforelse
</div>
</main>
<script>
const base = @json(url('/assessor/gabinetes/'.$cabinet));
const token = document.querySelector('meta[name="csrf-token"]').content;
async function send(path,method,data){
const res=await fetch(base+path,{method,headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify(data)});
if(!res.ok)throw new Error('Operação não concluída ('+res.status+').');
location.reload();
}
function wireForm(id,path,feedbackId){
document.querySelector(id).addEventListener('submit',async e=>{
e.preventDefault();const feedback=document.querySelector(feedbackId);
const values=Object.fromEntries(new FormData(e.currentTarget));
for(const key of ['citizen_id','assessor_id','assigned_to','demand_id','due_date'])if(values[key]==='')delete values[key];
try{await send(path,'POST',values)}catch(err){feedback.className='error';feedback.textContent=err.message}
});
}
wireForm('#citizen-form','/cidadaos','#citizen-feedback');
wireForm('#appointment-form','/agenda','#appointment-feedback');
wireForm('#task-form','/tarefas','#task-feedback');
document.querySelector('#demand-form').addEventListener('submit',async e=>{
e.preventDefault();const f=e.currentTarget,feedback=document.querySelector('#feedback');
try{await send('/demandas','POST',Object.fromEntries(new FormData(f)))}catch(err){feedback.className='error';feedback.textContent=err.message}
});
async function showHistory(id){
const target=document.querySelector('#history-panel');target.textContent='Carregando histórico...';
try{
const response=await fetch(base+'/demandas/'+id+'/historico',{headers:{'Accept':'application/json'}});
if(!response.ok)throw new Error('Histórico indisponível');
const result=await response.json();
target.replaceChildren();
const title=document.createElement('h2');title.textContent='Histórico do protocolo #'+id;target.appendChild(title);
for(const event of result.data){
const row=document.createElement('p');
row.textContent=event.created_at+' — '+event.event+(event.note?' ('+event.note+')':'');
target.appendChild(row);
}
if(!result.data.length){const empty=document.createElement('p');empty.textContent='Sem eventos registrados';target.appendChild(empty)}
}catch(err){target.textContent=err.message}
}
async function changeTaskStatus(id,status){
try{await send('/tarefas/'+id+'/status','PATCH',{status})}
catch(e){alert(e.message);location.reload()}
}
async function assignDemand(id,assessor){if(!assessor)return;try{await send('/demandas/'+id+'/atribuir','POST',{assessor_id:Number(assessor)})}catch(e){alert(e.message);location.reload()}}
async function changeStatus(id,status){try{await send('/demandas/'+id+'/status','PATCH',{status})}catch(e){alert(e.message);location.reload()}}
</script></body></html>
