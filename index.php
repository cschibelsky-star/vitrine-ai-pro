<?php
require_once __DIR__ . '/includes/functions.php';
$premiumVersion = @filemtime(__DIR__ . '/assets/css/vitrine-premium.css') ?: '1';
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#080d17">
  <title>VITRINE IA PRO — Tecnologia com propósito</title>
  <meta name="description" content="Conheça os projetos da Vitrine IA Pro: inteligência artificial, comunicação, cidades e soluções digitais que conectam ideias ao mundo real.">
  <link rel="icon" href="/assets/img/favicon.png" type="image/png">
  <link rel="stylesheet" href="/assets/css/vitrine-premium.css?v=<?= (int) $premiumVersion ?>">
  <script defer src="/assets/js/vitrine-premium.js?v=<?= (int) $premiumVersion ?>"></script>
</head>
<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header">
  <a class="brand" href="/" aria-label="Vitrine IA Pro — início"><img src="/assets/img/logo-vitrine-ai-pro-header.png" width="230" height="70" alt="VITRINE IA PRO"></a>
  <button class="menu-toggle" type="button" aria-controls="site-nav" aria-expanded="false"><span>Menu</span><span aria-hidden="true">☰</span></button>
  <nav id="site-nav" aria-label="Navegação principal">
    <a href="#projetos">Projetos</a><a href="#aplicacoes">No mundo real</a><a href="#ecossistema">Ecossistema</a>
    <a class="nav-contact" href="/contato.php">Vamos conversar <span aria-hidden="true">↗</span></a>
  </nav>
</header>
<main id="conteudo">
  <section class="hero" aria-labelledby="hero-title">
    <div class="hero-copy">
      <p class="eyebrow"><span class="status-dot" aria-hidden="true"></span> Inteligência que conecta</p>
      <h1 id="hero-title">Tecnologia<br>com <span>propósito.</span></h1>
      <p class="hero-description">Um ecossistema de projetos digitais que aproxima pessoas, transforma a comunicação e abre novas possibilidades para cidades e organizações.</p>
      <div class="hero-actions"><a class="button primary" href="#projetos">Explore nossos projetos <span aria-hidden="true">↗</span></a><a class="text-link" href="#aplicacoes">Conheça as aplicações <span aria-hidden="true">↓</span></a></div>
      <div class="hero-signature"><span class="signature-line" aria-hidden="true"></span><p>Ideias conectadas.<br><strong>Experiências reais.</strong></p></div>
    </div>
    <div class="hero-art" aria-hidden="true">
      <div class="art-grid"></div><div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="orbit orbit-three"></div>
      <div class="core-glow"></div><div class="core"><img src="/assets/img/logo-vitrine-ai-pro-icon.png" width="110" height="110" alt=""></div>
      <div class="art-label label-top"><span class="label-icon">◈</span><div><small>CIDADES & TERRITÓRIOS</small><strong>Conexões locais</strong></div></div>
      <div class="art-label label-right"><span class="label-icon purple">▶</span><div><small>COMUNICAÇÃO DIGITAL</small><strong>Novas experiências</strong></div></div>
      <div class="art-label label-bottom"><span class="label-icon">✳</span><div><small>INTELIGÊNCIA APLICADA</small><strong>Possibilidades reais</strong></div></div>
      <span class="art-coordinate">VITRINE / ECOSSISTEMA DIGITAL</span><span class="art-cross cross-top">+</span><span class="art-cross cross-bottom">+</span>
    </div>
  </section>
  <div class="principles" aria-label="Áreas de atuação"><span>Inteligência artificial</span><span>Produtos digitais</span><span>Comunicação</span><span>Cidades conectadas</span><span>Inovação com propósito</span></div>
  <section class="section projects" id="projetos" aria-labelledby="projects-title">
    <div class="section-heading"><div><p class="eyebrow">01 / Nossos projetos</p><h2 id="projects-title">Um universo de<br><span>possibilidades.</span></h2></div><p>Cada projeto tem sua própria identidade.<br>Todos compartilham o mesmo propósito:<br>conectar tecnologia às necessidades reais.</p></div>
    <div class="project-grid">
      <article class="project-card guide-card">
        <div class="card-top"><span class="project-category">Cidades & territórios</span><span class="project-number">01</span></div>
        <div class="project-symbol" aria-hidden="true">⌖</div><h3>Guia Digital<br>da Cidade<span class="registered">®</span></h3><p>Descubra lugares, histórias e experiências. Uma nova forma de conhecer e valorizar cada cidade.</p>
        <a class="card-link" href="/guia-digital.php">Conhecer o projeto <span aria-hidden="true">↗</span></a><div class="card-landscape" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>
      </article>
      <article class="project-card tv-card">
        <div class="card-top"><span class="project-category">Comunicação & mídia</span><span class="project-number">02</span></div>
        <div class="project-symbol" aria-hidden="true">▶</div><h3>TV Digital<br>Enterprise</h3><p>Conteúdo, informação e identidade em uma plataforma de comunicação digital.</p><a class="card-link" href="/tv-digital.php">Conhecer o projeto <span aria-hidden="true">↗</span></a><div class="card-waves" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
      </article>
      <article class="project-card government-card">
        <div class="card-top"><span class="project-category">Setor público</span><span class="project-number">03</span></div>
        <div class="project-symbol" aria-hidden="true">▦</div><h3>Governo<br>Digital IA</h3><p>Projetos digitais para aproximar a gestão pública das pessoas e das necessidades do território.</p><a class="card-link" href="/governo.php">Conhecer o projeto <span aria-hidden="true">↗</span></a><div class="card-circles" aria-hidden="true"></div>
      </article>
      <article class="project-card health-card">
        <div class="card-top"><span class="project-category">Tecnologia & saúde</span><span class="project-number">04</span></div>
        <div class="project-symbol" aria-hidden="true">+</div><h3>SISMED</h3><p>Uma nova frente do ecossistema dedicada a soluções digitais para a área da saúde.</p><span class="development-badge"><span aria-hidden="true"></span> Em desenvolvimento</span><div class="health-graphic" aria-hidden="true">+</div>
      </article>
    </div>
  </section>
  <section class="section applications" id="aplicacoes" aria-labelledby="applications-title">
    <div class="section-heading"><div><p class="eyebrow">02 / No mundo real</p><h2 id="applications-title">Tecnologia que<br><span>ganha vida.</span></h2></div><p>Projetos que conectam o digital<br>às histórias e à identidade de Sumaré.</p></div>
    <div class="case-grid">
      <a class="case-card" href="https://www.conhecasumare.com.br/" target="_blank" rel="noopener noreferrer"><img src="/assets/img/cases/conheca-sumare-showcase.jpg" alt="Conheça Sumaré, aplicação do Guia Digital da Cidade" width="800" height="500" loading="lazy"><div class="case-overlay"><p class="eyebrow">Guia Digital da Cidade®</p><h3>Conheça Sumaré</h3><p>Uma cidade. Muitas histórias para descobrir.</p><span class="case-arrow" aria-hidden="true">↗</span></div><span class="sr-only"> — abrir em nova aba</span></a>
      <a class="case-card" href="/casos-reais.php"><img src="/assets/img/cases/tv-sumare-showcase.jpg" alt="TV Sumaré, aplicação da TV Digital Enterprise" width="800" height="500" loading="lazy"><div class="case-overlay"><p class="eyebrow">TV Digital Enterprise</p><h3>TV Sumaré</h3><p>A informação local em uma experiência digital.</p><span class="case-arrow" aria-hidden="true">↗</span></div></a>
    </div>
  </section>
  <section class="section ecosystem" id="ecossistema" aria-labelledby="ecosystem-title">
    <div class="ecosystem-intro"><p class="eyebrow">03 / A Vitrine</p><h2 id="ecosystem-title">Projetos diferentes.<br><span>Uma visão em comum.</span></h2><p>A Vitrine IA Pro reúne iniciativas que exploram o potencial da inteligência artificial e da tecnologia. Aqui você conhece os projetos, suas aplicações e o caminho para explorar cada solução.</p><a class="text-link" href="/contato.php">Fale com a Vitrine <span aria-hidden="true">↗</span></a></div>
    <div class="value-list"><article><span>01</span><div><h3>Inteligência com direção</h3><p>Tecnologia pensada a partir de problemas concretos e das pessoas que precisam resolvê-los.</p></div></article><article><span>02</span><div><h3>Identidade em cada projeto</h3><p>Produtos com propostas próprias, conectados por uma visão compartilhada de inovação.</p></div></article><article><span>03</span><div><h3>Conexão com o mundo real</h3><p>O digital como parte da vida das cidades, da comunicação e das organizações.</p></div></article></div>
  </section>
  <section class="closing"><p class="eyebrow">O próximo passo começa com uma ideia</p><h2>Explore. Conheça.<br><span>Conecte-se.</span></h2><a class="button primary" href="#projetos">Encontre seu próximo projeto <span aria-hidden="true">↗</span></a><div class="closing-orbit" aria-hidden="true"></div></section>
</main>
<footer class="site-footer"><div class="footer-top"><a class="brand" href="/" aria-label="Vitrine IA Pro — início"><img src="/assets/img/logo-vitrine-ai-pro-header.png" width="230" height="70" alt="VITRINE IA PRO" loading="lazy"></a><p>Inteligência artificial.<br>Tecnologia com propósito.</p><div><a href="#projetos">Projetos</a><a href="/contato.php">Contato</a><a href="/lgpd.php">Privacidade</a></div></div><div class="footer-bottom"><span>© <?= date('Y') ?> Vitrine IA Pro</span><span>Ideias conectadas. Experiências reais.</span><a href="#conteudo">Voltar ao topo ↑</a></div></footer>
</body>
</html>
