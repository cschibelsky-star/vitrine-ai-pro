<?php
$title='Resultado do Diagnóstico';
require __DIR__.'/includes/header.php';
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()){
  echo '<section class="section"><h1>Diagnóstico não processado.</h1><p class="lead">Por segurança, reinicie o diagnóstico.</p><a class="btn" href="/diagnostico.php">Voltar ao diagnóstico</a></section>';
  require __DIR__.'/includes/footer.php'; exit;
}
$perfil = clean_input($_POST['perfil'] ?? 'Organização');
$cidade = clean_input($_POST['cidade'] ?? '');
$objetivo = clean_input($_POST['objetivo'] ?? '');
$solucao = 'Cidade Inteligente 360'; $link='/demos/cidade-inteligente-360/'; $plano='Aplicação Integrada';
$orientacao='Indicado quando a organização precisa enxergar comunicação pública, mídia e TV Digital funcionando de forma integrada.';
if (stripos($perfil,'Câmara')!==false){$solucao='Câmara 360';$link='/demos/camara-360/';$plano='Governo Digital — Legislativo';$orientacao='Indicado para legislativos que precisam dar mais visibilidade a sessões, proposições, vereadores e comunicação audiovisual.';}
elseif (stripos($perfil,'TV')!==false || stripos($perfil,'Audiovisual')!==false){$solucao='TV 360';$link='/demos/tv-360/';$plano='TV Digital';$orientacao='Indicado para operações que precisam organizar transmissões, programação, entrevistas, podcasts e acervo audiovisual.';}
elseif (stripos($perfil,'Notícias')!==false || stripos($perfil,'Rádio')!==false || stripos($perfil,'Jornal')!==false){$solucao='News 360';$link='/demos/news-360/';$plano='Comunicação e Mídia';$orientacao='Indicado para veículos regionais que buscam crescer audiência, organizar conteúdo e estruturar monetização.';}
elseif (stripos($perfil,'Prefeitura')!==false || stripos($perfil,'Secretaria')!==false){$solucao='Município 360';$link='/demos/municipio-360/';$plano='Governo Digital';$orientacao='Indicado para administrações públicas que precisam fortalecer comunicação oficial, transparência e relacionamento com o cidadão.';}
?>
<section class="section"><span class="eyebrow dark">Recomendação estratégica</span><h1><?= e($plano) ?></h1><p class="lead">Com base no perfil informado, este é o caminho mais adequado para iniciar a análise comercial.</p><div class="diagnosticResult"><h2><?= e($solucao) ?></h2><p><strong>Organização:</strong> <?= e($perfil) ?> <?= $cidade ? '• '.e($cidade) : '' ?></p><p><strong>Objetivo informado:</strong> <?= e($objetivo) ?></p><p><strong>Orientação:</strong> <?= e($orientacao) ?></p><p><strong>Próxima ação recomendada:</strong> avaliar o cenário indicado, confirmar os módulos necessários e solicitar uma apresentação com proposta personalizada.</p><div class="actions"><a class="btn" href="<?= e($link) ?>">Abrir cenário indicado</a><a class="btn ghost" href="/solicitacao-institucional.php">Solicitar proposta</a></div></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
