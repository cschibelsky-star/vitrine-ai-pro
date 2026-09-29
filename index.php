<?php
$version='4.1.0-PREMIUM-HML';
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Conheça Sumaré — Descubra e viva mais a sua cidade</title>
<meta name="description" content="Descubra lugares, eventos, cultura, gastronomia, hospedagem e experiências em Sumaré.">
<meta name="theme-color" content="#0b3d2d">
<link rel="manifest" href="/manifest.json?v=4.1">
<link rel="stylesheet" href="/assets/style.css?v=4.1">
</head>
<body>
<header class="site-header">
  <a class="brand" href="#inicio"><span class="brand-mark">CS</span><span><strong>Conheça Sumaré</strong><small>Guia Digital da Cidade</small></span></a>
  <nav><a href="#descubra">Explorar</a><a href="#agenda">Eventos</a><a href="#roteiros">Roteiros</a><a href="#parceiros">Participar</a></nav>
  <button class="header-install" data-install>Instalar no celular</button>
</header>

<main>
<section class="hero" id="inicio">
  <div class="hero-image"></div><div class="hero-overlay"></div>
  <div class="hero-inner">
    <div class="hero-copy">
      <span class="eyebrow"><i></i> Sumaré, São Paulo</span>
      <h1>Descubra Sumaré.<br><em>Viva mais a sua cidade.</em></h1>
      <p>Encontre lugares, experiências, cultura, eventos, sabores e histórias para aproveitar Sumaré de um jeito novo.</p>
      <div class="actions"><a class="btn light" href="#descubra">Explorar Sumaré <b>→</b></a><a class="btn ghost" href="#agenda">O que fazer hoje</a></div>
      <form class="search" onsubmit="event.preventDefault();document.querySelector('#descubra').scrollIntoView({behavior:'smooth'})"><span>⌕</span><input placeholder="Busque lugares, experiências e eventos"><button>Buscar</button></form>
    </div>
    <aside class="hero-card">
      <small>SEU PRÓXIMO PASSEIO</small><strong>Comece pelo que combina com você.</strong>
      <a href="#natureza">🌿 Natureza</a><a href="#cultura">🏛 Cultura</a><a href="#sabores">🍽 Gastronomia</a><a href="#agenda">✦ Eventos</a>
    </aside>
  </div>
</section>

<section class="section" id="descubra">
  <div class="container">
    <div class="heading split"><div><span>DO SEU JEITO</span><h2>O que você quer fazer em Sumaré?</h2></div><p>Escolha uma experiência e encontre opções para hoje, para o fim de semana ou para montar seu próprio roteiro.</p></div>
    <div class="experience-grid">
      <a class="experience large" id="natureza" style="--img:url('https://conhecasumare.com.br/assets/img/real/natureza.jpg')"><small>NATUREZA & LAZER</small><strong>Respire, caminhe, desacelere.</strong><b>Explorar →</b></a>
      <a class="experience" style="--img:url('https://conhecasumare.com.br/assets/img/real/evento.jpg')"><small>AGENDA</small><strong>Acontecendo em Sumaré</strong><b>Ver eventos →</b></a>
      <a class="experience" id="sabores" style="--img:url('https://conhecasumare.com.br/assets/img/real/gastronomia.jpg')"><small>SABORES</small><strong>Onde comer</strong><b>Descobrir →</b></a>
      <a class="experience" style="--img:url('https://conhecasumare.com.br/assets/img/real/hospedagem.jpg')"><small>HOSPEDAGEM</small><strong>Onde ficar</strong><b>Encontrar →</b></a>
      <a class="experience" style="--img:url('https://conhecasumare.com.br/assets/img/real/comercio.jpg')"><small>COMÉRCIO LOCAL</small><strong>Compre de quem faz a cidade</strong><b>Conhecer →</b></a>
    </div>
  </div>
</section>

<section class="section soft" id="cultura">
  <div class="container">
    <div class="heading"><span>VALE CONHECER</span><h2>Lugares que contam Sumaré.</h2><p>Natureza, memória, cultura e pontos de encontro para redescobrir a cidade.</p></div>
    <div class="places">
      <article class="place featured"><img src="https://conhecasumare.com.br/images/atrativos/bosque-dallorto/capa-real.jpg" alt="Bosque do Jardim Dall'Orto"><div><small>NATUREZA</small><h3>Bosque do Jardim Dall'Orto</h3><p>Ver detalhes →</p></div></article>
      <article class="place"><img src="https://conhecasumare.com.br/images/atrativos/pro-memoria/capa-real.jpg" alt="Pró-Memória"><div><small>CULTURA & HISTÓRIA</small><h3>Pró-Memória</h3><p>Ver detalhes →</p></div></article>
      <article class="place"><img src="https://conhecasumare.com.br/images/atrativos/praca-das-bandeiras/capa-real.jpg" alt="Praça das Bandeiras"><div><small>CIDADE</small><h3>Praça das Bandeiras</h3><p>Ver detalhes →</p></div></article>
      <article class="place"><img src="https://conhecasumare.com.br/images/atrativos/represa-marcelo-pedroni/capa-real.jpg" alt="Represa Marcelo Pedroni"><div><small>NATUREZA</small><h3>Represa Marcelo Pedroni</h3><p>Ver detalhes →</p></div></article>
    </div>
  </div>
</section>

<section class="section agenda" id="agenda">
  <div class="container">
    <div class="heading split"><div><span>AGENDA DA CIDADE</span><h2>Sempre tem algo acontecendo.</h2></div><a href="https://cultura.sumare.sp.gov.br/" target="_blank" rel="noopener">Fonte oficial Cultura ↗</a></div>
    <div class="agenda-grid">
      <article class="event-card main"><img src="https://conhecasumare.com.br/images/eventos/feira-artesanato/capa-real.jpg" alt="Feira de Artesanato"><div class="date"><b>•</b><span>AGENDA</span></div><div><small>FEIRAS & ECONOMIA CRIATIVA</small><h3>Feira de Artesanato</h3><p>Programação local e experiências da cidade.</p></div></article>
      <article class="event-card"><div class="date"><b>•</b><span>CULTURA</span></div><div><small>PROGRAMAÇÃO</small><h3>Agenda Cultural</h3><p>Eventos e atividades com referência nas fontes oficiais.</p></div></article>
      <article class="event-card"><div class="date"><b>•</b><span>SUMARÉ</span></div><div><small>EXPERIÊNCIAS</small><h3>Fim de semana na cidade</h3><p>Descubra novas opções para aproveitar Sumaré.</p></div></article>
    </div>
    <div class="source-note"><span>✓</span><p><strong>Referências oficiais.</strong> A curadoria editorial considera informações públicas da Secretaria Municipal de Turismo e da Secretaria Municipal de Cultura.</p><div><a href="https://turismo.sumare.sp.gov.br/" target="_blank">Turismo ↗</a><a href="https://cultura.sumare.sp.gov.br/" target="_blank">Cultura ↗</a></div></div>
  </div>
</section>

<section class="section" id="roteiros">
  <div class="container">
    <div class="heading"><span>EXPERIÊNCIAS PRONTAS</span><h2>Escolha um roteiro e vá.</h2><p>Ideias para aproveitar Sumaré sozinho, em família ou com amigos.</p></div>
    <div class="route-grid">
      <article><i>☀</i><small>ROTEIRO SUGERIDO</small><h3>Um dia em Sumaré</h3><p>Natureza, cultura, sabores e pontos marcantes em uma experiência leve.</p><a href="#descubra">Explorar →</a></article>
      <article><i>👨‍👩‍👧</i><small>ROTEIRO SUGERIDO</small><h3>Sumaré em família</h3><p>Opções para passear, brincar, comer e aproveitar junto.</p><a href="#descubra">Explorar →</a></article>
      <article><i>🏛</i><small>ROTEIRO SUGERIDO</small><h3>Cultura & memória</h3><p>Um caminho para conhecer histórias, patrimônio e identidade local.</p><a href="#cultura">Explorar →</a></article>
    </div>
  </div>
</section>

<section class="section app" id="app">
  <div class="container app-inner">
    <div><span>CONHEÇA SUMARÉ NO CELULAR</span><h2>A cidade com você,<br>onde você estiver.</h2><p>Instale o Conheça Sumaré e tenha acesso rápido a lugares, agenda, mapa, favoritos e, em breve, benefícios exclusivos do Passaporte.</p><ul><li>✓ Instalação rápida</li><li>✓ Favoritos e rotas</li><li>✓ Experiência otimizada para celular</li></ul><button class="btn yellow" data-install>Instalar agora</button></div>
    <div class="phone"><div class="phone-screen"><img src="https://conhecasumare.com.br/assets/img/hero-real.jpg" alt=""><div><small>CONHEÇA SUMARÉ</small><strong>O que vamos descobrir hoje?</strong><span>🌿 Natureza</span><span>✦ Eventos</span><span>🍽 Sabores</span></div></div></div>
  </div>
</section>

<section class="section passport">
  <div class="container passport-inner">
    <div><span>PRÓXIMA EXPERIÊNCIA</span><h2>Passaporte<br>Conheça Sumaré</h2><p>Uma nova forma de descobrir negócios locais, viver experiências e acessar benefícios em parceiros da cidade.</p><b class="pill">Em desenvolvimento</b></div>
    <div class="passport-card"><small>CONHEÇA SUMARÉ</small><b>PASSAPORTE</b><strong>Descubra. Visite.<br>Viva Sumaré.</strong><footer><span>•••• 2026</span><span>SUMARÉ · SP</span></footer></div>
  </div>
</section>

<section class="section partner" id="parceiros">
  <div class="container partner-inner">
    <div><span>PARA QUEM FAZ SUMARÉ ACONTECER</span><h2>Sua empresa também pode fazer parte.</h2><p>Cadastre seu negócio no Conheça Sumaré e prepare-se para alcançar moradores e visitantes que procuram onde comer, comprar, se hospedar e viver novas experiências.</p><div class="chips"><b>Perfil no guia</b><b>Presença nas categorias</b><b>Futuras campanhas e benefícios</b></div></div>
    <aside><small>COMECE AGORA</small><strong>Cadastro inicial gratuito</strong><p>Inclua sua empresa na base do projeto. Recursos de destaque e Passaporte serão disponibilizados em etapas.</p><a class="btn yellow" href="https://conhecasumare.com.br/cadastro-empresa.php">Cadastrar minha empresa →</a></aside>
  </div>
</section>
</main>

<footer class="footer"><div class="footer-brand"><span class="brand-mark">CS</span><div><strong>Conheça Sumaré</strong><small>Guia Digital da Cidade</small></div></div><div class="tech"><span>VIA</span><div><small>TECNOLOGIA DESENVOLVIDA PELA</small><strong>Vitrine IA Pro</strong></div></div><small class="version">HML · <?=htmlspecialchars($version)?></small></footer>

<div class="install-sheet" id="installSheet"><button id="closeSheet">×</button><h3>Instale o Conheça Sumaré</h3><p>No Android, use o Chrome e escolha “Instalar app” ou “Adicionar à tela inicial”. No iPhone, use o Safari e escolha “Adicionar à Tela de Início”.</p></div>
<script src="/assets/app.js?v=4.1"></script>
</body></html>