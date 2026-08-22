<?php
$title='Solicitação Comercial';
$prefModelo=clean_input($_GET['modelo'] ?? '',255);
$prefPerfil=clean_input($_GET['perfil'] ?? '',255);
$prefCidade=clean_input($_GET['cidade'] ?? '',255);
$prefNecessidade=clean_input($_GET['necessidade'] ?? '',2000);
$prefModeloComercial=clean_input($_GET['modelo_comercial'] ?? '',255);
require __DIR__.'/includes/header.php';
?>
<section class="section"><span class="eyebrow dark">Solicitação comercial</span><h1>Solicite apresentação, escopo ou proposta.</h1><p class="lead">Conte sua necessidade e o modelo de operação desejado. A VITRINE IA PRO avalia se o melhor caminho é SaaS, personalização, White Label ou desenvolvimento sob medida. Para órgãos públicos, a contratação continua sujeita aos procedimentos administrativos e legais aplicáveis.</p>
<form class="formGrid" method="post" action="/salvar-solicitacao.php"><?= csrf_field() ?>
<label>Tipo de organização<select name="perfil" required><option value="">Selecione</option><?php foreach(['Prefeitura ou Secretaria','Câmara Municipal','Autarquia / Fundação / Consórcio','Portal de Notícias / Rádio / Jornal','TV Web / Projeto Audiovisual','Empresa / Comércio / Prestador de Serviços','Imobiliária / Corretor','ONG / Associação / Terceiro Setor','Operador de Turismo / Cidade','Marca / Profissional / Equipe de Comunicação','Instituição de Ensino / Projeto Educacional','Outro'] as $op): ?><option<?= $prefPerfil===$op?' selected':'' ?>><?= e($op) ?></option><?php endforeach; ?></select></label>
<label>Órgão, cidade ou empresa<input name="organizacao" required placeholder="Nome da organização"></label>
<label>Nome do responsável<input name="nome" required placeholder="Nome completo"></label>
<label>Cargo/Função<input name="cargo" placeholder="Ex: Diretor, Secretário, Proprietário"></label>
<label>E-mail<input name="email" type="email" required placeholder="email@dominio.com"></label>
<label>WhatsApp<input name="telefone" required placeholder="DDD + número"></label>
<label>Cidade/Estado<input name="cidade" value="<?= e($prefCidade) ?>" placeholder="Ex: Sumaré/SP"></label>
<label>Produto ou solução de interesse<select name="modelo" required><option value="">Selecione</option><?php foreach(['TV Digital Enterprise','Portal News AI Pro','Guia Digital da Cidade®','Vitrine Social Media','Governo Digital IA','Desenvolvimento de Soluções Digitais','Cursos IA','SISMED','AssessorGov IA','Ainda não definido'] as $op): ?><option<?= $prefModelo===$op?' selected':'' ?>><?= e($op) ?></option><?php endforeach; ?></select></label>
<label>Modelo comercial preferido<select name="modelo_comercial"><option>Ainda não sei</option><?php foreach(['SaaS','SaaS personalizado','White Label','Projeto sob medida'] as $op): ?><option<?= $prefModeloComercial===$op?' selected':'' ?>><?= e($op) ?></option><?php endforeach; ?></select></label>
<label>Finalidade<select name="finalidade"><option>Solicitar apresentação</option><option>Solicitar diagnóstico</option><option>Solicitar proposta técnica</option><option>Solicitar proposta comercial</option><option>Solicitar documentação para contratação pública</option><option>Agendar reunião</option><option>Projeto piloto</option></select></label>
<label class="full">Necessidade principal<textarea name="necessidade" required placeholder="Descreva o que você precisa, o que deseja automatizar, integrar, vender ou escalar."><?= e($prefNecessidade) ?></textarea></label>
<label class="full"><input type="checkbox" name="consentimento_lgpd" value="1" required> Autorizo o uso destes dados para contato comercial e atendimento da solicitação, conforme a política de privacidade.</label>
<div class="full notice"><strong>Para órgãos públicos:</strong> o envio da solicitação não gera contratação automática. A forma de contratação, quando houver interesse, deverá ser definida pelo ente público contratante conforme análise interna, legislação aplicável e procedimentos administrativos próprios.</div>
<button class="btn">Enviar solicitação</button></form></section>
<?php require __DIR__.'/includes/footer.php'; ?>
