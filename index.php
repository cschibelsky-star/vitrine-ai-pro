<?php
require_once __DIR__ . '/includes/functions.php';
$showcaseVersion = @filemtime(__DIR__ . '/assets/css/vitrine-showcase.css') ?: 1;
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#031025">
  <meta name="description" content="Vitrine IA Pro: conheça nosso ecossistema de projetos digitais e suas aplicações reais em cidades, comunicação, gestão pública e tecnologia.">
  <title>Vitrine IA Pro — Tecnologia com propósito para projetos reais</title>
  <link rel="icon" href="/assets/img/favicon.png" type="image/png">
  <link rel="stylesheet" href="/assets/css/vitrine-showcase.css?v=<?= (int)$showcaseVersion ?>">
  <script defer src="/assets/js/vitrine-showcase.js?v=<?= (int)$showcaseVersion ?>"></script>
</head>
<body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="header" id="inicio">
 <div class="nav-wrap">
  <a class="brand" href="/" aria-label="Vitrine IA Pro — página inicial">
   <span class="brand-v" aria-hidden="true"><span></span><span></span></span>
   <span class="brand-words"><strong>VITRINE</strong><small>— IA PRO —</small></span>
  </a>
  <button class="menu-toggle" aria-controls="nav-menu" aria-expanded="false" type="button"><span>Menu</span><span class="hamburger">☰</span></button>
  <nav class="nav" id="nav-menu" aria-label="Menu principal">
   <a href="#inicio" class="active">Início</a>
   <a href="#projetos">Projetos</a>
   <a href="#aplicacoes">No mundo real</a>
   <a href="#ecossistema">Ecossistema</a>
   <a href="/contato.php">Contato</a>
  </nav>
  <div class="nav-extra">
    <button class="search-toggle" aria-label="Pesquisar projetos" aria-expanded="false" aria-controls="project-search" type="button"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg></button>
    <a class="button primary nav-cta" href="#projetos">Explorar projetos <span aria-hidden="true">→</span></a>
  </div>
 </div>
 <div class="search-panel" id="project-search" hidden><label for="project-query">Pesquisar no portfólio</label><input id="project-query" type="search" placeholder="Buscar projeto ou área de atuação..."><span class="search-results" aria-live="polite"></span></div>
</header>
<main id="conteudo">
<section class="hero section-shell" aria-labelledby="hero-title">
 <div class="hero-copy">
  <p class="eyebrow">Ecossistema de projetos digitais</p>
  <h1 id="hero-title">Tecnologia com<br>propósito para<br><span>projetos reais.</span></h1>
  <p class="lead">A Vitrine IA Pro é um ecossistema que apresenta projetos digitais que conectam inteligência, comunicação, cidades e aplicações no mundo real, mostrando como a tecnologia pode gerar impacto positivo na sociedade.</p>
  <div class="hero-buttons"><a class="button primary" href="#projetos">Explorar projetos <span aria-hidden="true">→</span></a><a class="button outline" href="#aplicacoes"><span class="play-icon" aria-hidden="true">▷</span> Conhecer aplicações</a></div>
  <ul class="hero-values" aria-label="Valores da Vitrine IA Pro"><li><span class="value-icon">✺</span>Inteligência<br>aplicada</li><li><span class="value-icon">▱</span>Projetos<br>reais</li><li><span class="value-icon">↗</span>Impacto<br>na sociedade</li><li><span class="value-icon">◎</span>Conexão entre<br>pessoas e cidades</li></ul>
 </div>
 <div class="hero-visual" aria-label="Visualização da conexão entre projetos e cidades" role="img">
  <div class="city-lights" aria-hidden="true"></div>
  <div class="platform-glow" aria-hidden="true"></div>
  <div class="holo-ring holo-ring-one" aria-hidden="true"></div><div class="holo-ring holo-ring-two" aria-hidden="true"></div>
  <div class="globe" aria-hidden="true"><i class="latitude lat1"></i><i class="latitude lat2"></i><i class="latitude lat3"></i><i class="longitude lon1"></i><i class="longitude lon2"></i><i class="longitude lon3"></i><span class="continent americas"></span><span class="continent africa"></span><span class="continent asia"></span><b class="node n1"></b><b class="node n2"></b><b class="node n3"></b><b class="node n4"></b><b class="node n5"></b><b class="node n6"></b><b class="node n7"></b><b class="node n8"></b></div>
  <div class="holo-card projects-holo"><div class="holo-caption">Projetos em destaque</div><div class="holo-number">4 <span class="bars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span></div><div class="holo-divider"></div><div class="holo-caption">Áreas de atuação</div><ul><li>▦ &nbsp; Cidades</li><li>▤ &nbsp; Comunicação</li><li>▥ &nbsp; Gestão pública</li><li>✳ &nbsp; Inteligência artificial</li></ul></div>
  <div class="holo-card impact-holo"><div class="holo-caption">Impacto real</div><div class="impact-line" aria-hidden="true">↗</div><span>Cidades<br>Pessoas<br>Resultados</span></div>
  <div class="holo-card map-holo"><div class="holo-caption">Tecnologia que conecta</div><svg viewBox="0 0 150 105" fill="none" aria-hidden="true"><path d="M17 20 48 12 59 21 80 13 93 27 118 29 123 45 138 53 118 75 108 91 90 79 66 86 58 69 40 70 28 56 10 46Z" stroke="#0bc5ff" stroke-width="2"/><g stroke="#0bc5ff" stroke-width="1.1" opacity=".65"><path d="M25 22 74 47 108 86M45 61 93 25 125 52M20 47 118 74M75 15 58 70"/></g><g fill="#53e8ff"><circle cx="25" cy="22" r="3"/><circle cx="74" cy="47" r="3"/><circle cx="108" cy="86" r="3"/><circle cx="45" cy="61" r="3"/><circle cx="93" cy="25" r="3"/><circle cx="125" cy="52" r="3"/></g></svg></div>
  <div class="holo-card reality-holo"><span class="round-arrow" aria-hidden="true">→</span><span>Do projeto<br>à realidade</span></div>
 </div>
</section>
<section class="projects section-shell" id="projetos" aria-labelledby="projects-title">
 <div class="section-heading"><div><p class="eyebrow">Nossos projetos</p><h2 id="projects-title">Soluções para um futuro mais inteligente.</h2></div><a class="section-link" href="/produtos.php">Ver todos os projetos <span>→</span></a></div>
 <div class="project-grid">
  <article class="project-card guide" data-keywords="guia digital cidade turismo conheça sumaré bem viver comércio eventos">
    <div class="project-inner"><div class="project-title-row"><span class="project-icon">▧</span><h3>Guia Digital<br>da Cidade<sup>®</sup></h3></div><p>Guia conectado de turismo, comércio, serviços e informações da cidade.</p></div>
    <div class="project-image"><img src="/assets/img/cases/conheca-sumare-showcase.jpg" loading="lazy" width="800" height="480" alt="Aplicação do Guia Digital, com destaque ao Conheça Sumaré"></div>
    <a href="/guia-digital.php" class="project-link">Ver projeto <span aria-hidden="true">→</span></a>
  </article>
  <article class="project-card tv" data-keywords="tv digital enterprise televisão sumare notícias conteúdo comunicação streaming">
    <div class="project-inner"><div class="project-title-row"><span class="project-icon">▣</span><h3>TV Digital<br>Enterprise</h3></div><p>Plataforma para canais de TV, transmissões ao vivo, conteúdo e experiências digitais.</p></div>
    <div class="project-image"><img src="/assets/img/cases/tv-sumare-showcase.jpg" loading="lazy" width="800" height="480" alt="Plataforma e estúdio TV Digital Enterprise"></div>
    <a href="/tv-digital.php" class="project-link">Ver projeto <span aria-hidden="true">→</span></a>
  </article>
  <article class="project-card gov" data-keywords="governo digital inteligência artificial gestão pública serviços">
    <div class="project-inner"><div class="project-title-row"><span class="project-icon">▥</span><h3>Governo<br>Digital IA</h3></div><p>Soluções para gestão pública com inteligência artificial, transparência e serviços digitais.</p></div>
    <div class="project-image art-building" role="img" aria-label="Representação arquitetônica de um projeto de governo digital"><span class="building-roof"></span><span class="building-body"><i></i><i></i><i></i><i></i><i></i><i></i></span></div>
    <a href="/governo.php" class="project-link">Ver projeto <span aria-hidden="true">→</span></a>
  </article>
  <article class="project-card med" data-keywords="sismed saúde tecnologia sistema médico em desenvolvimento">
    <div class="project-inner"><div class="project-title-row"><span class="project-icon">✚</span><h3>SISMED<br><small>Em desenvolvimento</small></h3></div><p>Uma frente do ecossistema de soluções digitais para a área da saúde.</p></div>
    <div class="project-image art-health" role="img" aria-label="Ilustração conceitual de painéis de saúde e tecnologia"><div class="health-screen"><span>✚</span><i></i><i></i><i></i></div></div>
    <span class="project-link disabled">Em desenvolvimento</span>
  </article>
 </div>
 <p class="empty-search" hidden>Nenhum projeto encontrado. Tente outro termo.</p>
</section>
<section class="applications section-shell" id="aplicacoes" aria-labelledby="applications-title">
 <div class="section-heading"><div><p class="eyebrow">No mundo real</p><h2 id="applications-title">Projetos que já fazem a diferença.</h2></div><a class="section-link" href="/casos-reais.php">Ver mais cases <span>→</span></a></div>
 <div class="case-grid">
  <article class="case-card sumare-case"><img src="/assets/img/cases/conheca-sumare-showcase.jpg" loading="lazy" width="800" height="500" alt="Interface demonstrativa do projeto Conheça Sumaré"><div class="case-content"><div class="case-title"><span class="case-icon">●</span><h3>Conheça Sumaré</h3></div><p>Guia digital com informações sobre atrações, eventos, turismo e cultura para valorizar a cidade.</p><a class="button primary small" href="https://www.conhecasumare.com.br/" target="_blank" rel="noopener noreferrer">Ver projeto <span aria-hidden="true">→</span></a></div></article>
  <article class="case-card tv-case"><img src="/assets/img/cases/tv-sumare-showcase.jpg" loading="lazy" width="800" height="500" alt="TV Sumaré, aplicação da TV Digital Enterprise"><div class="case-content"><div class="case-title"><span class="case-icon">▣</span><h3>TV Sumaré</h3></div><p>Plataforma de TV Digital com conteúdos locais, comunicação e integração com a cidade.</p><a class="button primary small" href="/casos-reais.php">Ver case completo <span aria-hidden="true">→</span></a></div></article>
 </div>
</section>
<section class="ecosystem section-shell" id="ecossistema" aria-labelledby="ecosystem-title">
 <div class="ecosystem-copy"><p class="eyebrow">O conceito Vitrine</p><h2 id="ecosystem-title">Um ecossistema<br>com visão de futuro.</h2><p>Mais do que projetos isolados, a Vitrine IA Pro é uma vitrine de soluções que unem tecnologia, identidade e impacto real, mostrando como a inteligência digital pode transformar cidades e conectar pessoas.</p></div>
 <div class="ecosystem-cards">
  <article><span class="ecosystem-icon">✺</span><h3>Inteligência<br>com direção</h3><p>Tecnologia aplicada com objetivos claros, foco em resultados e nas pessoas.</p></article>
  <article><span class="ecosystem-icon violet">▱</span><h3>Identidade<br>em cada projeto</h3><p>Soluções que respeitam a realidade de cada cidade, setor e comunidade.</p></article>
  <article><span class="ecosystem-icon cyan">◎</span><h3>Conexão com<br>o mundo real</h3><p>Projetos que saem do conceito e se tornam aplicações práticas com impacto na sociedade.</p></article>
 </div>
</section>
</main>
<footer class="footer"><div class="section-shell footer-main">
 <div class="footer-brand"><a class="brand" href="/" aria-label="Vitrine IA Pro — início"><span class="brand-v" aria-hidden="true"><span></span><span></span></span><span class="brand-words"><strong>VITRINE</strong><small>— IA PRO —</small></span></a><p>Tecnologia com propósito<br>para projetos reais.</p></div>
 <div class="footer-column"><h3>Institucional</h3><a href="#inicio">Início</a><a href="#projetos">Projetos</a><a href="#aplicacoes">No mundo real</a><a href="#ecossistema">Ecossistema</a><a href="/contato.php">Contato</a></div>
 <div class="footer-column"><h3>Projetos</h3><a href="/guia-digital.php">Guia Digital da Cidade</a><a href="/tv-digital.php">TV Digital Enterprise</a><a href="/governo.php">Governo Digital IA</a><span>SISMED</span></div>
 <div class="footer-column"><h3>Ecossistema</h3><span>Cidades</span><span>Comunicação</span><span>Gestão pública</span><span>Inteligência artificial</span></div>
 <div class="footer-column"><h3>Contato</h3><span>⌖ Sumaré — SP, Brasil</span><a href="mailto:vitrineiapro@gmail.com">✉ vitrineiapro@gmail.com</a></div>
 </div><div class="footer-bottom section-shell"><span>© <?= date('Y') ?> Vitrine IA Pro. Todos os direitos reservados.</span><div><a href="/lgpd.php">Política de Privacidade</a><a href="/termos.php">Termos de Uso</a></div></div></footer>
</body>
</html>