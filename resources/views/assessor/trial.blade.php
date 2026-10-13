<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Avaliação | Assessor Online</title>
<style>body{margin:0;background:#f1f6fa;color:#102443;font:16px system-ui}main{max-width:720px;margin:48px auto;padding:24px}section{background:white;border:1px solid #dce6ef;border-radius:17px;padding:28px}h1{font-size:2.4rem}label{display:block;margin:18px 0}input,select,button{font:inherit;padding:12px;border-radius:8px}input:not([type=checkbox]),select{display:block;width:100%;box-sizing:border-box;border:1px solid #64748b;margin-top:8px}button{background:#36d7e6;color:#061b32;border:0;font-weight:700;min-height:48px}p{line-height:1.6}:focus-visible{outline:3px solid #086c83;outline-offset:3px}</style></head>
<body><main><h1>Assessor Online.</h1><section><h2>Avaliação de 30 dias · HML</h2>
<p>Sem cartão e sem cobrança automática. O prazo começa quando o ambiente isolado e o acesso forem confirmados. Contratação exige uma nova decisão explícita.</p>
<p>Cadastro experimental vinculado ao gabinete já cadastrado e verificado no Core. Use somente dados sintéticos nesta etapa.</p>
@if($errors->any())<p role="alert">{{ $errors->first() }}</p>@endif
@if($trial)
<p>Estado: {{ $trial->status }}</p>
@if($trial->starts_at)<p>Início (UTC): {{ $trial->starts_at }}<br>Término (UTC): {{ $trial->ends_at }}</p>@endif
<p>Encerrar a avaliação bloqueia o acesso; a revogação no provedor pode permanecer pendente. A avaliação não é reiniciada.</p>
@if(!in_array($trial->status,['cancelled','expired']))<form method="post" action="/assessor/avaliacao/{{ $trial->id }}/cancelar">@csrf<button>Cancelar avaliação</button></form>@endif
@else
<form method="post" action="/assessor/avaliacao">@csrf
<input type="hidden" name="terms_version" value="{{ config('assessor_trial.terms_version') }}">
<label>Nome fictício do gabinete<input name="cabinet_name" maxlength="120" required value="{{ old('cabinet_name') }}"></label>
<label>Tipo de mandato<select name="mandate_type" required><option value="vereador">Vereador</option><option value="deputado">Deputado</option></select></label>
<p>Finalidade: registrar e administrar a avaliação. São usados o identificador do usuário e do gabinete no Core, nome informado, aceite e datas. Nenhum dado é enviado a marketing. O provedor recebe apenas os identificadores e o hostname. Não cadastre dados de cidadãos.</p>
<p>Exportação, retenção, atendimento aos direitos dos titulares e aviso de privacidade definitivo precisam ser homologados antes de contas reais.</p>
<label><input type="checkbox" name="consent" value="1" required> Concordo com os termos experimentais acima e autorizo o tratamento para esta avaliação em HML.</label>
<button>Solicitar avaliação em homologação</button></form>
@endif</section></main></body></html>
