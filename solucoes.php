<?php
$segmento = strtolower(trim((string)($_GET['segmento'] ?? '')));
$origem = trim((string)($_GET['origem'] ?? 'solucoes'));
$segmentos = [
  'empresas' => ['titulo'=>'Digitalize sua empresa com uma solução preparada para crescer.','texto'=>'CRM, atendimento, vendas, portais, automações e sistemas operacionais podem partir de uma necessidade concreta e evoluir em modelo SaaS, personalizado ou White Label.','perfil'=>'Empresa / Comércio / Prestador de Serviços','necessidade'=>'CRM / vendas / atendimento'],
  'imobiliario' => ['titulo'=>'Organize imóveis, leads e atendimento em uma operação digital própria.','texto'=>'Estruture captação, cadastro de imóveis, funil comercial, portal, atendimento e automações em uma solução adequada à rotina da imobiliária.','perfil'=>'Imobiliária / Corretor','necessidade'=>'Sistema ou plataforma sob medida'],
  'terceiro-setor' => ['titulo'=>'Organize projetos, relacionamento e captação em uma única operação digital.','texto'=>'ONGs, associações e organizações podem estruturar cadastros, projetos, documentos, campanhas, atendimento e acompanhamento de resultados.','perfil'=>'ONG / Associação / Terceiro Setor','necessidade'=>'Sistema ou plataforma sob medida'],
  'governo' => ['titulo'=>'Modernize comunicação, atendimento e serviços digitais do órgão.','texto'=>'A implantação começa pelo diagnóstico institucional e pode envolver portal, documentos, agenda, atendimento digital, transparência e integrações definidas por escopo.','perfil'=>'Prefeitura ou Secretaria','necessidade'=>'Governo digital / comunicação pública'],
  'midia' => ['titulo'=>'Transforme conteúdo, audiência e monetização em uma operação digital própria.','texto'=>'Portais, rádios, TVs Web e projetos de mídia podem estruturar publicação, vídeo, RSS, IA editorial, newsletter e recursos comerciais.','perfil'=>'Portal de Notícias / Rádio / Jornal','necessidade'=>'Portal de notícias / mídia regional'],
  'turismo' => ['titulo'=>'Conecte turismo, eventos, negócios locais e experiências da cidade.','texto'=>'Estruture atrativos, eventos, gastronomia, hospedagem, comércio, mapas e operação territorial em uma experiência digital própria.','perfil'=>'Operador de Turismo / Cidade','necessidade'=>'Turismo / guia digital da cidade']
];
$campanha = $segmentos[$segmento] ?? null;
$title = $campanha ? 'Solução para '.ucwords(str_replace('-',' ',$segmento)) : 'Soluções por Necessidade';
$description = 'Encontre o melhor caminho digital a partir da necessidade da sua operação.';
require __DIR__.'/includes/header.php';
function qs_diag(array $extra=[]): string {
  global $segmento,$origem,$campanha;
  $base=['segmento'=>$segmento,'origem'=>$origem];
  if($campanha){ $base['perfil']=$campanha['perfil']; $base['necessidade_tipo']=$campanha['necessidade']; }
  return '/diagnostico.php?'.http_build_query(array_filter(array_merge($base,$extra),fn($v)=>$v!==''));
}
?>
<section class="hero heroPremium marketHero campaignHero">
  <div class="heroGrid">
    <div>
      <span class="eyebrow"><?= $campanha ? 'Solução para seu segmento' : 'Comece pela necessidade' ?></span>
      <h1><?= e($campanha['titulo'] ?? 'Qual problema você precisa resolver?') ?></h1>
      <p><?= e($campanha['texto'] ?? 'Você não precisa escolher um software antes de falar conosco. Conte o problema, o público e o resultado esperado. A VITRINE IA PRO identifica se o melhor caminho é uma solução pronta, uma base personalizada ou um desenvolvimento específico.') ?></p>
      <div class="actions"><a class="btn" href="<?= e(qs_diag()) ?>">Iniciar diagnóstico</a><a class="btn ghost" href="/produtos.php">Ver produtos</a></div>
      <div class="heroTrust"><span>SaaS</span><span>Personalizado</span><span>White Label</span><span>Desenvolvimento</span></div>
    </div>
    <div class="officialIdentityCard campaignCard">
      <div class="previewTop"><strong>Da necessidade à solução</strong><span>um caminho comercial simples e objetivo</span></div>
      <div class="previewGrid"><article><b>01</b><span>Problema</span></article><article><b>02</b><span>Diagnóstico</span></article><article><b>03</b><span>Direcionamento</span></article><article><b>04</b><span>Proposta</span></article></div>
      <div class="identityPills"><span>Empresas</span><span>Imobiliário</span><span>Organizações</span><span>Governo</span></div>
    </div>
  </div>
</section>

<section class="section needSection"><span class="eyebrow dark">Escolha pelo objetivo</span><h2>Que resultado você quer alcançar?</h2><p class="lead">Cada opção leva ao mesmo diagnóstico, já contextualizado para acelerar o direcionamento comercial.</p><div class="grid three needGrid">
<article class="card needCard"><h3>Organizar clientes e vendas</h3><p>CRM, funil comercial, cadastros, atendimento, propostas, automações e indicadores.</p><a class="textLink" href="<?= e(qs_diag(['necessidade_tipo'=>'CRM / vendas / atendimento'])) ?>">Quero organizar vendas →</a></article>
<article class="card needCard"><h3>Digitalizar uma operação</h3><p>Sistemas internos, painéis, workflows, integrações e acompanhamento de processos.</p><a class="textLink" href="<?= e(qs_diag(['necessidade_tipo'=>'Sistema ou plataforma sob medida'])) ?>">Quero digitalizar processos →</a></article>
<article class="card needCard"><h3>Criar presença e canal digital</h3><p>Sites, portais, aplicativos, áreas de cliente e experiências digitais próprias.</p><a class="textLink" href="<?= e(qs_diag(['necessidade_tipo'=>'Site / portal / aplicativo'])) ?>">Quero criar um canal digital →</a></article>
<article class="card needCard"><h3>Produzir e distribuir conteúdo</h3><p>Portais de notícias, redes sociais, vídeos, TV Digital e fluxos editoriais.</p><a class="textLink" href="<?= e(qs_diag(['necessidade_tipo'=>'Portal de notícias / mídia regional'])) ?>">Quero estruturar conteúdo →</a></article>
<article class="card needCard"><h3>Atender cidadãos ou públicos</h3><p>Comunicação institucional, documentos, solicitações, transparência e atendimento.</p><a class="textLink" href="<?= e(qs_diag(['necessidade_tipo'=>'Governo digital / comunicação pública'])) ?>">Quero melhorar atendimento →</a></article>
<article class="card needCard"><h3>Automatizar com IA</h3><p>Assistentes, classificação, geração de conteúdo, dados, integrações e automações.</p><a class="textLink" href="<?= e(qs_diag(['necessidade_tipo'=>'Integrações / automações / IA'])) ?>">Quero aplicar IA →</a></article>
</div></section>

<section class="section alt segmentStrip"><span class="eyebrow dark">Entradas por segmento</span><h2>Campanhas podem começar pelo contexto da operação.</h2><div class="segmentLinks"><a href="/solucoes.php?segmento=empresas&origem=segmentos">Empresas</a><a href="/solucoes.php?segmento=imobiliario&origem=segmentos">Imobiliário</a><a href="/solucoes.php?segmento=terceiro-setor&origem=segmentos">Terceiro Setor</a><a href="/solucoes.php?segmento=midia&origem=segmentos">Mídia</a><a href="/solucoes.php?segmento=turismo&origem=segmentos">Cidades e Turismo</a><a href="/solucoes.php?segmento=governo&origem=segmentos">Governo</a></div></section>

<section class="section cta compactCta"><span class="eyebrow dark">Próximo passo</span><h2>Explique a necessidade. Nós estruturamos o caminho.</h2><p class="lead">O diagnóstico organiza a demanda e direciona para um produto existente, personalização, White Label ou desenvolvimento sob medida.</p><div class="actions" style="justify-content:center"><a class="btn" href="<?= e(qs_diag()) ?>">Iniciar diagnóstico</a><a class="btn ghost" href="/solicitacao-institucional.php">Solicitar proposta</a></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>