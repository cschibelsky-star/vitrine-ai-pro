<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Demandas — Assessor Online</title>
<style>body{font:16px system-ui;background:#0c1222;color:#f1f5ff;margin:0;padding:2rem}main{max-width:1100px;margin:auto}a{color:#8ed5ff}table{width:100%;border-collapse:collapse}td,th{padding:1rem;border-bottom:1px solid #39465b;text-align:left}input,select,button{padding:.65rem;margin:.25rem;background:#17233a;color:white;border:1px solid #51627d;border-radius:6px}.scroll{overflow-x:auto}</style></head>
<body><main><h1>Demandas do gabinete</h1>
<form method="get" action="{{ route('assessor.demands.index') }}"><label>Pesquisar <input name="search" maxlength="180" value="{{ $filters['search'] ?? '' }}" placeholder="Protocolo ou assunto"></label><label>Situação <select name="status"><option value="">Todas</option>@foreach(['received'=>'Recebida','triaged'=>'Triada','referred'=>'Encaminhada','in_progress'=>'Em andamento','resolved'=>'Resolvida','closed'=>'Encerrada'] as $value=>$label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label><button type="submit">Filtrar</button></form>
<div class="scroll"><table><thead><tr><th>Protocolo</th><th>Assunto</th><th>Cidadão</th><th>Situação</th><th>Atualização</th></tr></thead><tbody>
@forelse($cases as $case)<tr><td><a href="{{ route('assessor.demands.show', ['caseId'=>$case->id]) }}">{{ $case->protocol }}</a></td><td>{{ $case->subject }}</td><td>{{ $case->citizen_name }}</td><td>{{ $case->status }}</td><td>{{ $case->updated_at }}</td></tr>
@empty<tr><td colspan="5">Nenhuma demanda encontrada.</td></tr>@endforelse
</tbody></table></div>{{ $cases->links() }}</main></body></html>
