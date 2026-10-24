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
<section>
<div class="panel"><h2>Nova demanda</h2>
<form id="demand-form"><label>Assunto<input name="subject" maxlength="200" required></label><label>Descrição<textarea name="description" maxlength="10000" rows="5" required></textarea></label><button type="submit">Registrar demanda</button></form><p id="feedback" role="status"></p>
<h2>Equipe</h2>@foreach($members as $member)<p>{{ $member->name }} <small>({{ $member->role }})</small></p>@endforeach
</div>
<div class="panel"><h2>Acompanhamento das demandas</h2><div style="overflow-x:auto"><table><thead><tr><th>Protocolo</th><th>Assunto</th><th>Responsável</th><th>Situação</th></tr></thead><tbody>
@forelse($demands as $d)
<tr><td>#{{ $d->id }}</td><td>{{ $d->subject }}</td><td>
<select aria-label="Responsável pela demanda {{ $d->id }}" onchange="assignDemand({{ $d->id }},this.value)">
<option value="">Não atribuído</option>@foreach($members as $m)<option value="{{ $m->id }}" @selected($d->assigned_to == $m->id)>{{ $m->name }}</option>@endforeach
</select></td><td><select aria-label="Situação da demanda {{ $d->id }}" onchange="changeStatus({{ $d->id }},this.value)">
@foreach(['open'=>'Aberta','in_progress'=>'Em andamento','resolved'=>'Resolvida'] as $value=>$label)<option value="{{ $value }}" @selected($d->status === $value)>{{ $label }}</option>@endforeach
</select></td></tr>
@empty<tr><td colspan="4">Nenhuma demanda registrada.</td></tr>@endforelse
</tbody></table></div></div></section>
</main>
<script>
const base = @json(url('/assessor/gabinetes/'.$cabinet));
const token = document.querySelector('meta[name="csrf-token"]').content;
async function send(path,method,data){
const res=await fetch(base+path,{method,headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify(data)});
if(!res.ok)throw new Error('Operação não concluída ('+res.status+').');
location.reload();
}
document.querySelector('#demand-form').addEventListener('submit',async e=>{
e.preventDefault();const f=e.currentTarget,feedback=document.querySelector('#feedback');
try{await send('/demandas','POST',Object.fromEntries(new FormData(f)))}catch(err){feedback.className='error';feedback.textContent=err.message}
});
async function assignDemand(id,assessor){if(!assessor)return;try{await send('/demandas/'+id+'/atribuir','POST',{assessor_id:Number(assessor)})}catch(e){alert(e.message);location.reload()}}
async function changeStatus(id,status){try{await send('/demandas/'+id+'/status','PATCH',{status})}catch(e){alert(e.message);location.reload()}}
</script></body></html>
