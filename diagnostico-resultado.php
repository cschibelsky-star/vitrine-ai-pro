<?php
$title='Resultado do Diagnóstico';
require __DIR__.'/includes/header.php';
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()){
  echo '<section class="section"><h1>Diagnóstico não processado.</h1><p class="lead">Por segurança, reinicie o diagnóstico.</p><a class="btn" href="/diagnostico.php">Voltar ao diagnóstico</a></section>';
  require __DIR__.'/includes/footer.php'; exit;
}
$perfil = clean_input($_POST['perfil'] ?? 'Organização');
$cidade = clean_input($_POST['cidade'] ?? '');
$necessidade = clean_input($_POST['necessidade_tipo'] ?? '');
$objetivo = clean_input($_POST['objetivo'] ?? '');
$txt = function_exists('mb_strtolower') ? mb_strtolower($perfil.' '.$necessidade.' '.$objetivo, 'UTF-8') : strtolower($perfil.' '.$necessidade.' '.$objetivo);
$produto='Desenvolvimento de Soluções Digitais'; $link='/desenvolvimento.php'; $orientacao='Indicado quando a necessidade exige uma solução específica, integração entre sistemas ou fluxo que não cabe em um produto pronto.';
if(strpos($txt,'tv')!==false || strpos($txt,'audiovisual')!==false || strpos($txt,'transmiss')!==false){$produto='TV Digital Enterprise';$link='/tv-digital.php';$orientacao='Indicado para operações com vídeos, transmissões, programação, podcasts, acervo e presença audiovisual própria.';}
elseif(strpos($txt,'notícia')!==false || strpos($txt,'noticia')!==false || strpos($txt,'rádio')!==false || strpos($txt,'radio')!==false || strpos($txt,'jornal')!==false || strpos($txt,'mídia')!==false || strpos($txt,'midia')!==false){$produto='Portal News AI Pro';$link='/news.php';$orientacao='Indicado para veículos e operações editoriais que precisam organizar publicação, audiência, automações e monetização.';}
elseif(strpos($txt,'turismo')!==false || strpos($txt,'guia')!==false || strpos($txt,'cidade')!==false){$produto='Guia Digital da Cidade®';$link='/guia-digital.php';$orientacao='Indicado para cidades, operadores e projetos territoriais que precisam organizar turismo, eventos, gastronomia, hospedagem, comércio e mapas.';}
elseif(strpos($txt,'rede social')!==false || strpos($txt,'redes sociais')!==false || strpos($txt,'conteúdo')!==false || strpos($txt,'conteudo')!==false || strpos($txt,'marca')!==false){$produto='Vitrine Social Media';$link='/social-media.php';$orientacao='Indicado para marcas e equipes que precisam organizar produção recorrente de conteúdo, identidade, calendário e fluxo editorial.';}
elseif(strpos($txt,'prefeitura')!==false || strpos($txt,'secretaria')!==false || strpos($txt,'câmara')!==false || strpos($txt,'camara')!==false || strpos($txt,'governo')!==false || strpos($txt,'públic')!==false || strpos($txt,'public')!==false){$produto='Governo Digital IA';$link='/governo.php';$orientacao='Indicado para órgãos públicos que precisam estruturar comunicação oficial, transparência, atendimento digital, documentos e serviços.';}
elseif(strpos($txt,'curso')!==false || strpos($txt,'ensino')!==false || strpos($txt,'educa')!==false){$produto='Cursos IA';$link='/solicitacao-institucional.php';$orientacao='A plataforma educacional está em homologação. O caminho atual é avaliar escopo e projeto piloto.';}
elseif(strpos($txt,'saúde')!==false || strpos($txt,'saude')!==false){$produto='SISMED';$link='/solicitacao-institucional.php';$orientacao='A vertical de Saúde Digital IA está em desenvolvimento progressivo. O caminho atual é registrar interesse institucional e avaliar implantação futura.';}
$solicitacao='/solicitacao-institucional.php?modelo='.rawurlencode($produto).'&perfil='.rawurlencode($perfil).'&cidade='.rawurlencode($cidade).'&necessidade='.rawurlencode($objetivo);
?>
<section class="section"><span class="eyebrow dark">Recomendação estratégica</span><h1><?= e($produto) ?></h1><p class="lead">Com base nas respostas, este é o caminho mais adequado para iniciar a análise comercial.</p><div class="diagnosticResult"><p><strong>Perfil:</strong> <?= e($perfil) ?> <?= $cidade ? '• '.e($cidade) : '' ?></p><p><strong>Necessidade:</strong> <?= e($necessidade) ?></p><p><strong>Objetivo informado:</strong> <?= e($objetivo) ?></p><p><strong>Orientação:</strong> <?= e($orientacao) ?></p><p><strong>Próxima ação recomendada:</strong> conhecer a solução indicada e solicitar uma proposta com o escopo da sua operação.</p><div class="actions"><a class="btn" href="<?= e($link) ?>">Conhecer solução indicada</a><a class="btn ghost" href="<?= e($solicitacao) ?>">Solicitar proposta</a></div></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
