<?php
$version='4.2.3-VISUAL-HML';

$imageSources = [
  'bosque-jardim-dallorto' => 'https://turismo.sumare.sp.gov.br/public/img/natural_atractives/bosque-dos-lagos-sumare.jpeg',
  'pro-memoria' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/pro-memoria/capa-real.jpg',
  'orquidario-municipal' => 'https://turismo.sumare.sp.gov.br/public/img/natural_atractives/Orquidario-municipal.jpg',
  'represa-marcelo-pedroni' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/represa-marcelo-pedroni/capa-real.jpg',
  'praca-das-bandeiras' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/praca-das-bandeiras/capa-real.jpg',
  'horto-florestal-de-sumare' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/horto-florestal/capa.jpg',
  'ceav-centro-de-educacao-ambiental-vivenciada' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/ceav/capa.jpg',
  'igreja-matriz-de-santana' => 'https://turismo.sumare.sp.gov.br/public/img/historic_atractives/igreja-matriz-santana.jpg',
  'centro-historico-de-nova-veneza' => 'https://turismo.sumare.sp.gov.br/public/img/historic_atractives/centro-administrativo.jpeg',
  'shopping-parkcity-sumare' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/shopping-parkcity/capa.jpg',
  'recanto-dos-animais-henrique-pedroni' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/recanto-dos-animais/capa.jpg',
  'estancia-arvore-da-vida' => 'https://turismo.sumare.sp.gov.br/public/img/cultural_atractives/estancia.jpg',
  'capela-bom-jesus' => 'https://turismo.sumare.sp.gov.br/public/img/historic_atractives/igreja-bom-jesus.jpg',
  'casarao-sertaozinho' => 'https://turismo.sumare.sp.gov.br/public/img/historic_atractives/casarao-sertaozinho.jpeg',
  'categoria-natureza' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/assets/img/real/natureza.jpg',
  'categoria-evento' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/assets/img/real/evento.jpg',
  'categoria-gastronomia' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/assets/img/real/gastronomia.jpg',
  'categoria-hospedagem' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/assets/img/real/hospedagem.jpg',
  'categoria-comercio' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/assets/img/real/comercio.jpg',
  'evento-feira-artesanato' => 'https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/eventos/feira-artesanato/capa-real.jpg',
];

function conheca_image_url(string $key): string {
  return '/?img=' . rawurlencode($key);
}

if (isset($_GET['img'])) {
  $key = preg_replace('/[^a-z0-9-]/', '', strtolower((string)$_GET['img']));
  if (!$key || !isset($imageSources[$key])) {
    http_response_code(404);
    exit;
  }

  $cacheDir = __DIR__ . '/storage/image-cache';
  if (!is_dir($cacheDir)) {
    @mkdir($cacheDir, 0775, true);
  }

  $cacheFile = $cacheDir . '/' . $key . '.bin';
  if (!is_file($cacheFile) || filesize($cacheFile) < 512) {
    $context = stream_context_create([
      'http' => [
        'timeout' => 8,
        'follow_location' => 1,
        'header' => "User-Agent: ConhecaSumare/4.1\r\nAccept: image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8\r\n",
      ],
      'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
      ],
    ]);
    $data = @file_get_contents($imageSources[$key], false, $context);
    if ($data !== false && strlen($data) >= 512) {
      $tmp = $cacheFile . '.tmp';
      if (@file_put_contents($tmp, $data, LOCK_EX) !== false) {
        @rename($tmp, $cacheFile);
      }
    }
  }

  if (is_file($cacheFile) && filesize($cacheFile) >= 512) {
    $mime = 'image/jpeg';
    if (class_exists('finfo')) {
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $detected = $finfo->file($cacheFile);
      if (is_string($detected) && str_starts_with($detected, 'image/')) {
        $mime = $detected;
      }
    }
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=604800, stale-while-revalidate=86400');
    header('X-Content-Type-Options: nosniff');
    readfile($cacheFile);
    exit;
  }

  header('Content-Type: image/svg+xml; charset=UTF-8');
  header('Cache-Control: no-store');
  echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 750"><defs><linearGradient id="g" x1="0" x2="1"><stop stop-color="#dfe9e3"/><stop offset="1" stop-color="#f5f7f4"/></linearGradient></defs><rect width="1200" height="750" fill="url(#g)"/><circle cx="600" cy="315" r="72" fill="#0b6b46" opacity=".13"/><path d="M560 335l42-55 46 70 30-38 72 94H450z" fill="#0b6b46" opacity=".35"/><text x="600" y="470" text-anchor="middle" font-family="Arial,sans-serif" font-size="34" font-weight="700" fill="#365b4c">Foto em atualização</text></svg>';
  exit;
}

$atrativos = [
  'bosque-jardim-dallorto' => [
    'nome' => "Bosque dos Lagos Cidade Orquídea",
    'bairro' => "Jardim Dall'Orto",
    'categoria' => 'Natureza & lazer',
    'imagem' => conheca_image_url('bosque-jardim-dallorto'),
    'descricao' => 'Área verde com lagos e espaços para caminhar, contemplar e aproveitar momentos ao ar livre em Sumaré.',
    'historia' => 'Inaugurado em 2018, o Bosque dos Lagos ocupa uma área verde de aproximadamente 32 mil metros quadrados e reúne duas lagoas naturais e vegetação nativa. O espaço passou a integrar a oferta de lazer e convivência da cidade, recebendo moradores e visitantes em busca de tranquilidade e atividades ao ar livre.',
    'porque' => 'O projeto de estruturação prevê pista de caminhada, área para eventos, trilhas ecológicas, playground, quadras de vôlei e futebol de areia e pista para mountain bike, ampliando seu papel como ponto de lazer e contato com a natureza.',
    'endereco' => "Rua Piracanjuba, esquina com Rua Goiatuba - Jardim Dall'Orto"
  ],
  'pro-memoria' => [
    'nome' => 'Associação Pró-Memória de Sumaré',
    'bairro' => 'Centro',
    'categoria' => 'Cultura & história',
    'imagem' => conheca_image_url('pro-memoria'),
    'descricao' => 'Espaço dedicado à preservação da memória, da história e da identidade de Sumaré.',
    'historia' => 'A Associação Pró-Memória funciona no Centro de Memória Thomaz Didona, em um prédio construído em 1913, um dos mais antigos de Sumaré. A entidade atua na recuperação, preservação e divulgação da história local, organizando e conservando documentos, fotografias, jornais, livros e outros registros históricos.',
    'porque' => 'O acervo reúne cerca de 250 mil papéis, uma ampla coleção de jornais e mais de 117 mil fotografias digitalizadas, além de livros impressos e manuscritos. É uma das principais referências para quem quer conhecer a formação e a memória da cidade.',
    'endereco' => 'Praça da República, 102 - Centro'
  ],
  'orquidario-municipal' => [
    'nome' => 'Orquidário Municipal de Sumaré',
    'bairro' => 'Jardim Primavera',
    'categoria' => 'Cidade Orquídea',
    'imagem' => conheca_image_url('orquidario-municipal'),
    'descricao' => 'Atrativo relacionado à identidade de Sumaré como Cidade Orquídea, com foco em natureza e valorização da flora.',
    'historia' => 'O Orquidário Municipal ocupa uma área construída de aproximadamente 890 metros quadrados e foi implantado em parceria com empresas instaladas na região. O espaço foi concebido para reforçar a vocação de Sumaré ligada às orquídeas e valorizar esse elemento da identidade local.',
    'porque' => 'Logo na entrada, o visitante encontra espécies associadas à chamada orquídea de Sumaré, entre elas Cyrtopodium punctatum, nativa da região, e Cyrtopodium hatschbachi. A presença dessas espécies reforça a ligação simbólica entre a cidade e sua flora.',
    'endereco' => 'Av. Eugênia Biancalana Duarte, 200 - Jardim Primavera'
  ],
  'represa-marcelo-pedroni' => [
    'nome' => 'Represa Marcelo Pedroni',
    'bairro' => 'Sumaré',
    'categoria' => 'Natureza & lazer',
    'imagem' => conheca_image_url('represa-marcelo-pedroni'),
    'descricao' => 'Espaço associado à contemplação, caminhada e contato com a paisagem natural da cidade.',
    'historia' => 'A Represa Marcelo Pedroni faz parte da memória urbana de Sumaré desde a primeira metade do século XX. O Plano Diretor registra que, em 1934, foi construída no Sítio Sertãozinho uma estação de captação ligada ao primeiro serviço de abastecimento de água do município, utilizando a nascente que também abastecia a represa.',
    'porque' => 'Marcelo Pedroni, imigrante italiano ligado à implantação desse sistema de abastecimento, ficou conhecido como o “Pai da Água” de Sumaré. Hoje a represa continua sendo uma importante área de lazer e passa por revitalização com ciclovia, pista de caminhada, playground, deck panorâmico e outras melhorias previstas.',
    'endereco' => 'Rua da Represa Marcelo Pedroni'
  ],
];
$explorarItems = [
  ['slug'=>'bosque-jardim-dallorto','nome'=>'Bosque dos Lagos Cidade Orquídea','categoria'=>'Natureza','bairro'=>"Jardim Dall'Orto",'imagem'=>conheca_image_url('bosque-jardim-dallorto')],
  ['slug'=>'represa-marcelo-pedroni','nome'=>'Represa Marcelo Pedroni','categoria'=>'Natureza','bairro'=>'Sumaré','imagem'=>conheca_image_url('represa-marcelo-pedroni')],
  ['slug'=>'praca-das-bandeiras','nome'=>'Praça das Bandeiras','categoria'=>'Cultura e História','bairro'=>'Centro','imagem'=>conheca_image_url('praca-das-bandeiras')],
  ['slug'=>'pro-memoria','nome'=>'Associação Pró-Memória de Sumaré','categoria'=>'Cultura e História','bairro'=>'Centro','imagem'=>conheca_image_url('pro-memoria')],
  ['slug'=>'horto-florestal-de-sumare','nome'=>'Horto Florestal de Sumaré','categoria'=>'Natureza','bairro'=>'Sumaré','imagem'=>conheca_image_url('horto-florestal-de-sumare')],
  ['slug'=>'ceav-centro-de-educacao-ambiental-vivenciada','nome'=>'CEAV - Centro de Educação Ambiental Vivenciada','categoria'=>'Natureza','bairro'=>'Sumaré','imagem'=>conheca_image_url('ceav-centro-de-educacao-ambiental-vivenciada')],
  ['slug'=>'igreja-matriz-de-santana','nome'=>'Igreja Matriz de Sant’Ana','categoria'=>'Cultura e História','bairro'=>'Centro','imagem'=>conheca_image_url('igreja-matriz-de-santana')],
  ['slug'=>'orquidario-municipal','nome'=>'Orquidário Municipal de Sumaré','categoria'=>'Natureza','bairro'=>'Jardim Primavera','imagem'=>conheca_image_url('orquidario-municipal')],
  ['slug'=>'centro-historico-de-nova-veneza','nome'=>'Centro Histórico de Nova Veneza','categoria'=>'Cultura e História','bairro'=>'Nova Veneza','imagem'=>conheca_image_url('centro-historico-de-nova-veneza')],
  ['slug'=>'shopping-parkcity-sumare','nome'=>'Shopping ParkCity Sumaré','categoria'=>'Lazer e Família','bairro'=>'Sumaré','imagem'=>conheca_image_url('shopping-parkcity-sumare')],
  ['slug'=>'recanto-dos-animais-henrique-pedroni','nome'=>'Recanto dos Animais Henrique Pedroni','categoria'=>'Lazer e Família','bairro'=>'Sumaré','imagem'=>conheca_image_url('recanto-dos-animais-henrique-pedroni')],
  ['slug'=>'estancia-arvore-da-vida','nome'=>'Estância Árvore da Vida','categoria'=>'Lazer e Família','bairro'=>'Sumaré','imagem'=>conheca_image_url('estancia-arvore-da-vida')],
  ['slug'=>'capela-bom-jesus','nome'=>'Capela Bom Jesus','categoria'=>'Cultura e História','bairro'=>'Matão','imagem'=>conheca_image_url('capela-bom-jesus')],
  ['slug'=>'casarao-sertaozinho','nome'=>'Casarão Sertãozinho','categoria'=>'Cultura e História','bairro'=>'Sumaré','imagem'=>conheca_image_url('casarao-sertaozinho')]
];
$eventsFile = __DIR__ . '/storage/data/events.json';
$eventsData = [];
if (is_file($eventsFile)) {
  $decodedEvents = json_decode((string)file_get_contents($eventsFile), true);
  if (is_array($decodedEvents)) {
    $eventsData = $decodedEvents;
  }
}
$today = date('Y-m-d');
$publishedEvents = array_values(array_filter($eventsData, static function(array $event) use ($today): bool {
  if (($event['status'] ?? 'candidate') !== 'published') return false;
  $end = trim((string)($event['end_date'] ?? ''));
  $start = trim((string)($event['start_date'] ?? ''));
  $lastDay = $end !== '' ? $end : $start;
  return $start !== '' && ($lastDay === '' || $lastDay >= $today);
}));
usort($publishedEvents, static function(array $a, array $b): int {
  return strcmp((string)($a['start_date'] ?? '9999-12-31'), (string)($b['start_date'] ?? '9999-12-31'));
});

function conheca_event_date_label(array $event): string {
  $date = trim((string)($event['start_date'] ?? ''));
  if ($date === '') return 'DATA A CONFIRMAR';
  $ts = strtotime($date);
  if (!$ts) return strtoupper($date);
  $months = [1=>'JAN',2=>'FEV',3=>'MAR',4=>'ABR',5=>'MAI',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SET',10=>'OUT',11=>'NOV',12=>'DEZ'];
  return date('d', $ts) . ' ' . ($months[(int)date('n', $ts)] ?? '');
}

$pathOnly = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

if ($pathOnly === 'eventos') {
  ?><!doctype html>
  <html lang="pt-BR"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>Eventos em Sumaré — Conheça Sumaré</title>
  <meta name="description" content="Agenda cultural, turística e gastronômica de Sumaré.">
  <meta name="theme-color" content="#006e68">
  <link rel="manifest" href="/manifest.json?v=4.2.3">
  <link rel="stylesheet" href="/assets/style.css?v=4.2.3">
  </head><body class="faithful-home internal-page">
  <div class="faithful-page">
  <header class="f-header">
    <a class="f-logo" href="/"><span>CONHEÇA</span> <strong>SUMARÉ</strong></a>
    <nav><a href="/">Início</a><a href="/explorar">Turismo</a><a class="active" href="/eventos">Eventos</a><a href="/#gastronomia">Gastronomia</a><a href="/#negocios">Negócios</a><a href="/#mapa">Mapa</a></nav>
    <div class="f-actions"><a href="/explorar" class="f-search" aria-label="Buscar">⌕</a><button data-install>Baixar App</button></div>
  </header>
  <main class="i-main">
    <section class="i-hero i-events-hero">
      <div class="i-hero-copy"><small>AGENDA DE SUMARÉ</small><h1>Eventos, cultura e experiências para viver a cidade.</h1><p>Programação confirmada em fontes públicas, apresentada no mesmo padrão visual do Conheça Sumaré.</p></div>
    </section>
    <section class="i-section">
      <div class="i-heading"><div><small>✦ CONTEÚDO OFICIAL</small><h2>Próximos eventos</h2></div><a href="/">← Voltar ao início</a></div>
      <?php if(!$publishedEvents): ?>
        <div class="i-empty"><b>▣</b><div><h3>Novos eventos em monitoramento</h3><p>A agenda é atualizada quando surgem anúncios públicos confirmados. Assim que um evento estiver validado, ele aparecerá aqui.</p></div></div>
      <?php else: ?>
        <div class="i-event-grid">
        <?php foreach($publishedEvents as $event): ?>
          <article class="i-event-card">
            <?php if(!empty($event['image'])): ?><img src="<?=htmlspecialchars((string)$event['image'])?>" alt="<?=htmlspecialchars((string)($event['title']??''))?>" loading="lazy"><?php else: ?><div class="i-event-fallback">SUMARÉ</div><?php endif; ?>
            <div><small><?=htmlspecialchars((string)($event['category']??'Evento'))?> · <?=htmlspecialchars(conheca_event_date_label($event))?></small><h3><?=htmlspecialchars((string)($event['title']??''))?></h3><p><?=htmlspecialchars((string)($event['place']??''))?><?php if(!empty($event['start_time'])): ?> · <?=htmlspecialchars((string)$event['start_time'])?><?php endif; ?></p><?php if(!empty($event['summary'])): ?><p><?=htmlspecialchars((string)$event['summary'])?></p><?php endif; ?><?php if(!empty($event['source_url'])): ?><a href="<?=htmlspecialchars((string)$event['source_url'])?>" target="_blank" rel="noopener">Ver fonte oficial →</a><?php endif; ?></div>
          </article>
        <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
  <footer class="f-footer"><div class="f-footer-logo"><span>CONHEÇA</span> <strong>SUMARÉ</strong></div><nav><a href="/">Conheça Sumaré</a><a href="/explorar">Turismo</a><a href="/eventos">Eventos</a><a href="/#negocios">Negócios</a><a href="/#mapa">Mapa</a></nav><div class="f-tech"><small>Tecnologia e desenvolvimento</small><img src="https://raw.githubusercontent.com/cschibelsky-star/vitrine-ai-pro/main/assets/img/logo-vitrine-ai-pro.png" alt="Vitrine IA Pro"></div></footer>
  </div>
  <div class="install-sheet" id="installSheet"><button id="closeSheet">×</button><h3>Instale o Conheça Sumaré</h3><p>Use “Instalar app” ou “Adicionar à tela inicial”.</p></div>
  <script src="/assets/app.js?v=4.2.3"></script>
  </body></html><?php
  exit;
}

if ($pathOnly === 'explorar') {
  ?><!doctype html>
  <html lang="pt-BR"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>Explorar Sumaré — Conheça Sumaré</title>
  <meta name="description" content="Atrativos, natureza, cultura e experiências para conhecer Sumaré.">
  <meta name="theme-color" content="#006e68">
  <link rel="manifest" href="/manifest.json?v=4.2.3">
  <link rel="stylesheet" href="/assets/style.css?v=4.2.3">
  </head><body class="faithful-home internal-page">
  <div class="faithful-page">
  <header class="f-header">
    <a class="f-logo" href="/"><span>CONHEÇA</span> <strong>SUMARÉ</strong></a>
    <nav><a href="/">Início</a><a class="active" href="/explorar">Turismo</a><a href="/eventos">Eventos</a><a href="/#gastronomia">Gastronomia</a><a href="/#negocios">Negócios</a><a href="/#mapa">Mapa</a></nav>
    <div class="f-actions"><span class="f-search">⌕</span><button data-install>Baixar App</button></div>
  </header>
  <main class="i-main">
    <section class="i-hero i-explore-hero">
      <div class="i-hero-copy"><small>DESCUBRA SUMARÉ</small><h1>Explore os atrativos da cidade.</h1><p>Natureza, cultura, história, lazer e experiências para montar seu próprio roteiro.</p>
      <label class="i-searchbox">⌕<input id="exploreSearch" type="search" placeholder="Buscar atrativo, bairro ou categoria..."></label></div>
    </section>
    <section class="i-section">
      <div class="i-heading"><div><small>✦ GUIA DA CIDADE</small><h2>Lugares para conhecer</h2></div><span><?=count($explorarItems)?> atrativos</span></div>
      <div class="i-attraction-grid" id="exploreGrid">
      <?php foreach($explorarItems as $item): ?>
      <?php $exploreName = htmlspecialchars(strtolower($item['nome'].' '.$item['categoria'].' '.$item['bairro']), ENT_QUOTES, 'UTF-8'); $exploreHref = isset($atrativos[$item['slug']]) ? '/atrativo/'.rawurlencode($item['slug']) : '#'; ?>
      <a class="i-attraction-card" data-name="<?=$exploreName?>" href="<?=$exploreHref?>">
        <div class="i-attraction-photo" style="background-image:url('<?=htmlspecialchars($item['imagem'])?>')"><span><?=htmlspecialchars(strtoupper($item['categoria']))?></span></div>
        <div class="i-attraction-body"><h3><?=htmlspecialchars($item['nome'])?></h3><p>⌖ <?=htmlspecialchars($item['bairro'])?></p><b><?=isset($atrativos[$item['slug']]) ? 'Ver detalhes →' : 'Conteúdo em atualização'?></b></div>
      </a>
      <?php endforeach; ?>
      </div>
    </section>
  </main>
  <footer class="f-footer"><div class="f-footer-logo"><span>CONHEÇA</span> <strong>SUMARÉ</strong></div><nav><a href="/">Conheça Sumaré</a><a href="/eventos">Eventos</a><a href="/#gastronomia">Gastronomia</a><a href="/#negocios">Negócios</a><a href="/#mapa">Mapa</a></nav><div class="f-tech"><small>Tecnologia e desenvolvimento</small><strong>VITRINE<br><em>IA PRO</em></strong></div></footer>
  </div>
  <div class="install-sheet" id="installSheet"><button id="closeSheet">×</button><h3>Instale o Conheça Sumaré</h3><p>Use “Instalar app” ou “Adicionar à tela inicial”.</p></div>
  <script>const q=document.getElementById('exploreSearch');q?.addEventListener('input',()=>{const v=q.value.toLowerCase().trim();document.querySelectorAll('.i-attraction-card').forEach(c=>c.hidden=v&&!c.dataset.name.includes(v));});</script>
  <script src="/assets/app.js?v=4.2.3"></script>
  </body></html><?php
  exit;
}

$slug = isset($_GET['atrativo']) ? preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['atrativo'])) : '';
if ($slug && isset($atrativos[$slug])) {
  $a = $atrativos[$slug];
  ?><!doctype html>
  <html lang="pt-BR"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title><?=htmlspecialchars($a['nome'])?> — Conheça Sumaré</title>
  <meta name="description" content="<?=htmlspecialchars($a['descricao'])?>">
  <meta name="theme-color" content="#006e68">
  <link rel="manifest" href="/manifest.json?v=4.2.3">
  <link rel="stylesheet" href="/assets/style.css?v=4.2.3">
  </head><body class="faithful-home internal-page">
  <div class="faithful-page">
  <header class="f-header">
    <a class="f-logo" href="/"><span>CONHEÇA</span> <strong>SUMARÉ</strong></a>
    <nav><a href="/">Início</a><a class="active" href="/explorar">Turismo</a><a href="/eventos">Eventos</a><a href="/#gastronomia">Gastronomia</a><a href="/#negocios">Negócios</a><a href="/#mapa">Mapa</a></nav>
    <div class="f-actions"><a href="/explorar" class="f-search" aria-label="Voltar">←</a><button data-install>Baixar App</button></div>
  </header>
  <main class="i-main">
    <section class="i-detail-hero">
      <img src="<?=htmlspecialchars($a['imagem'])?>" alt="<?=htmlspecialchars($a['nome'])?>">
      <div class="i-detail-shade"></div>
      <div class="i-detail-title"><small><?=htmlspecialchars(strtoupper($a['categoria']))?></small><h1><?=htmlspecialchars($a['nome'])?></h1><p>⌖ <?=htmlspecialchars($a['bairro'])?> · Sumaré</p></div>
    </section>
    <section class="i-detail-content">
      <article>
        <small class="i-kicker">CONHEÇA O LOCAL</small><h2>Sobre este atrativo</h2><p><?=htmlspecialchars($a['descricao'])?></p>
        <div class="i-story"><small class="i-kicker">HISTÓRIA</small><h3>A história deste lugar</h3><p><?=htmlspecialchars($a['historia'])?></p></div>
        <div class="i-story"><small class="i-kicker">POR QUE CONHECER</small><h3>O que torna este lugar especial</h3><p><?=htmlspecialchars($a['porque'])?></p></div>
      </article>
      <aside><b>Planeje sua visita</b><p><?=htmlspecialchars($a['endereco'])?></p><small>Confirme horários e condições de visitação antes de sair.</small><a href="https://www.google.com/maps/search/?api=1&query=<?=urlencode($a['nome'].' Sumaré SP')?>" target="_blank" rel="noopener">Abrir no mapa →</a><a class="secondary" href="/explorar">← Ver outros atrativos</a></aside>
    </section>
  </main>
  <footer class="f-footer"><div class="f-footer-logo"><span>CONHEÇA</span> <strong>SUMARÉ</strong></div><nav><a href="/">Conheça Sumaré</a><a href="/explorar">Turismo</a><a href="/eventos">Eventos</a><a href="/#negocios">Negócios</a><a href="/#mapa">Mapa</a></nav><div class="f-tech"><small>Tecnologia e desenvolvimento</small><strong>VITRINE<br><em>IA PRO</em></strong></div></footer>
  </div>
  <div class="install-sheet" id="installSheet"><button id="closeSheet">×</button><h3>Instale o Conheça Sumaré</h3><p>Use “Instalar app” ou “Adicionar à tela inicial”.</p></div>
  <script src="/assets/app.js?v=4.2.3"></script>
  </body></html><?php
  exit;
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Conheça Sumaré — Turismo, cultura, eventos e negócios</title>
<meta name="description" content="Conheça Sumaré: turismo, cultura, eventos, gastronomia, mapa e oportunidades da cidade.">
<meta name="theme-color" content="#006e68">
<link rel="manifest" href="/manifest.json?v=4.2.3">
<link rel="stylesheet" href="/assets/style.css?v=4.2.3">
</head>
<body class="faithful-home">
<div class="faithful-page">
<header class="f-header">
  <a class="f-logo" href="/"><span>CONHEÇA</span> <strong>SUMARÉ</strong></a>
  <nav><a class="active" href="#inicio">Início</a><a href="#turismo">Turismo</a><a href="/eventos">Eventos</a><a href="#gastronomia">Gastronomia</a><a href="#negocios">Negócios</a><a href="#mapa">Mapa</a></nav>
  <div class="f-actions"><a href="/explorar" class="f-search" aria-label="Buscar">⌕</a><button data-install>Baixar App</button></div>
</header>

<main>
<section class="f-hero" id="inicio">
  <div class="f-hero-bg"></div><div class="f-hero-overlay"></div>
  <div class="f-hero-left">
    <h1>Descubra<br><em>Sumaré</em> no<br>seu celular.</h1>
    <p>Turismo, cultura, eventos, gastronomia<br>hospedagem, atrativos e experiências<br>locais em um guia digital moderno.</p>
    <div class="f-hero-buttons"><a href="/explorar"><b>➜</b> Explorar Sumaré <span>→</span></a><button data-install><b>▯</b> Instalar App</button></div>
    <div class="f-benefits">
      <div><b>♧</b><span>Conheça<br>nossa região</span></div>
      <div><b>♚</b><span>Viva a<br>cultura local</span></div>
      <div><b>▣</b><span>Acompanhe<br>eventos</span></div>
      <div><b>♡</b><span>Apoie o<br>comércio da cidade</span></div>
    </div>
  </div>
  <div class="f-phone">
    <div class="f-phone-screen">
      <div class="f-phone-top"><small>CONHEÇA</small><strong>SUMARÉ</strong><h3>Explore<br>Sumaré</h3><p>Turismo, cultura,<br>eventos e experiências</p></div>
      <div class="f-phone-search">⌕ &nbsp; O que você procura?</div>
      <div class="f-phone-icons">
        <span>♧<small>Atrações</small></span><span>▣<small>Eventos</small></span><span>♨<small>Gastronomia</small></span>
        <span>⚑<small>Hospedagem</small></span><span>⌖<small>Mapas</small></span><span>▣<small>Negócios</small></span>
      </div>
      <div class="f-phone-label"><b>Destaques</b><small>Negócios →</small></div>
      <div class="f-phone-card" style="background-image:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/bosque-dallorto/capa-real.jpg')"><span>Bosque dos Lagos<br>Sumaré</span></div>
    </div>
  </div>
  <div class="f-script">História<br>Cultura<br>Pessoas<br>Oportunidades</div>
  <div class="f-slogan">Sumaré<br>é para viver.</div>
</section>

<section class="f-categories" id="turismo">
  <a href="/explorar" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/bosque-dallorto/capa-real.jpg')"><i>♧</i><strong>Natureza</strong></a>
  <a href="#historia" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/igreja-matriz-santana/capa.jpg')"><i>⌂</i><strong>Cultura</strong></a>
  <a href="/eventos" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/eventos/feira-artesanato/capa-real.jpg')"><i>☕</i><strong>Eventos</strong></a>
  <a href="#mapa" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/assets/img/real/comercio.jpg')"><i>●</i><strong>Mapa</strong></a>
  <a id="gastronomia" href="/explorar" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/assets/img/real/gastronomia.jpg')"><i>♨</i><strong>Gastronomia</strong></a>
  <a href="/explorar" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/assets/img/real/hospedagem.jpg')"><i>▰</i><strong>Hospedagem</strong></a>
</section>

<section class="f-official">
  <div class="f-official-title">
    <div><small>✦ &nbsp; Conteúdo oficial</small><h2>Atrativos e eventos com referência nas fontes oficiais da cidade.</h2></div>
    <a href="/explorar">Ver todos os atrativos &nbsp;→</a>
  </div>
  <div class="f-feature-grid">
    <a class="f-feature" href="/atrativo/bosque-jardim-dallorto" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/bosque-dallorto/capa-real.jpg')"><div><small>NATUREZA</small><h3>Bosque dos Lagos</h3><p>Lazer, contato com a natureza<br>e um dos principais cartões-postais<br>de Sumaré.</p><b>›</b></div></a>
    <a class="f-feature" href="/atrativo/pro-memoria" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/pro-memoria/capa-real.jpg')"><div><small class="yellow">CULTURA</small><h3>Pró-Memória de Sumaré</h3><p>História, memória e identidade<br>da nossa cidade.</p><b>›</b></div></a>
    <a class="f-feature" href="/eventos" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/eventos/feira-artesanato/capa-real.jpg')"><div><small>EVENTOS</small><h3>Feira de Artesanato</h3><p>Talento local, cultura e economia<br>criativa reunidos em um só lugar.</p><b>›</b></div></a>
  </div>
</section>

<section class="f-history" id="historia">
  <div class="f-history-copy">
    <h2>Cultura e História de Sumaré</h2>
    <p>Uma cidade com raízes fortes, patrimônio histórico<br>e uma identidade construída por sua gente.</p>
    <a href="/atrativo/pro-memoria">Conheça nossa história &nbsp;→</a>
    <div class="f-mini-grid">
      <a href="/explorar" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/igreja-matriz-santana/capa.jpg')"><span>♟</span><b>Área Urbana e Rural</b></a>
      <a href="/eventos" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/eventos/feira-artesanato/capa-real.jpg')"><span>♚</span><b>Cultura popular</b></a>
      <a href="/explorar" style="--img:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/bosque-dallorto/capa-real.jpg')"><span>♧</span><b>Natureza e Lazer</b></a>
    </div>
  </div>
  <div class="f-history-photo" style="background-image:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/praca-das-bandeiras/capa-real.jpg')">
    <div class="f-patrimony"><b>▣</b><span>Patrimônio<br>que inspira<br>gerações</span></div>
  </div>
</section>

<section class="f-business" id="negocios">
  <div class="f-business-copy"><i>↗</i><h2>Sumaré também é<br><em>destino de negócios.</em></h2><p>Localização estratégica, infraestrutura completa,<br>conexão com os maiores centros do país e um ambiente<br>convidativo e seguro, atraindo grandes empresas e novos<br>investimentos.</p></div>
  <div class="f-business-grid">
    <article class="f-business-card" style="--biz:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/atrativos/shopping-parkcity/capa.jpg')"><div><strong>Shopping ParkCity Sumaré</strong><p>Comércio, serviços, lazer e fluxo regional.</p></div></article>
    <article class="f-business-card" style="--biz:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/assets/img/real/comercio.jpg')"><div><strong>Mercado Livre</strong><p>Operação logística e geração de oportunidades.</p></div></article>
    <article class="f-business-card" style="--biz:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/empresas/hotel-fildi/capa-real.jpg')"><div><strong>3M</strong><p>Indústria, tecnologia e presença empresarial em Sumaré.</p></div></article>
    <article class="f-business-card" style="--biz:url('https://raw.githubusercontent.com/cschibelsky-star/VitrineAI-FACTORY-ENTERPRISE-X/main/products/guia-digital-turismo/implementacao_4_3/images/empresas/hotel-jaguary/capa-real.jpg')"><div><strong>Logística e localização</strong><p>Conexão com os principais corredores rodoviários da região.</p></div></article>
  </div>
</section>

<section class="f-opportunities" id="mapa">
  <h2>Negócios e Oportunidades em Sumaré</h2>
  <div class="f-op-grid">
    <article><i>↗</i><b>Potencial econômico</b><p>Cidade em crescimento<br>consistente, com ambientes<br>favoráveis a novos negócios.</p></article>
    <article><i>●</i><b>Localização estratégica</b><p>Acesso fácil e rápido pelas<br>principais rodovias da região.</p></article>
    <article><i>♚</i><b>Apoio ao empreendedor</b><p>Uma cidade que valoriza<br>o incentivo local e<br>desenvolvimento regional.</p></article>
    <article><i>▣</i><b>Turismo de negócios</b><p>Eventos, serviços<br>e infraestrutura para o setor<br>corporativo.</p></article>
  </div>
</section>

<section class="f-join">
  <div class="f-join-copy"><b>▤</b><span><strong>Faça parte do Conheça Sumaré</strong><small>Sua empresa, evento ou iniciativa no guia digital da cidade.<br>Ganhe visibilidade para moradores, visitantes e novos negócios em Sumaré.</small></span></div>
  <div class="f-join-actions"><a href="/cadastro-empresa.php">✚ &nbsp; Participe do Guia &nbsp;→</a><a class="outline" href="/cadastro-empresa.php">▤ &nbsp; Saiba como divulgar</a></div>
</section>
</main>

<footer class="f-footer">
  <div class="f-footer-logo"><span>CONHEÇA</span> <strong>SUMARÉ</strong></div>
  <nav><a href="/">Conheça Sumaré</a><a href="/eventos">Eventos</a><a href="/explorar">Gastronomia</a><a href="/explorar">Negócios</a><a href="#mapa">Mapa</a><a href="#">Política de Privacidade</a><a href="https://turismo.sumare.sp.gov.br/" target="_blank" rel="noopener">Portal Oficial da Cidade</a></nav>
  <div class="f-tech"><small>Tecnologia e desenvolvimento</small><strong>VITRINE<br><em>IA PRO</em></strong></div>
</footer>
</div>

<div class="install-sheet" id="installSheet"><button id="closeSheet">×</button><h3>Instale o Conheça Sumaré</h3><p>No Android, use o Chrome e escolha “Instalar app” ou “Adicionar à tela inicial”. No iPhone, use o Safari e escolha “Adicionar à Tela de Início”.</p></div>
<script src="/assets/app.js?v=4.2.3"></script>
</body></html>