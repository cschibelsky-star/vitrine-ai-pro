<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Centro IA · Galeria PR #122 · Cockpit</title>
  <style>
    *{box-sizing:border-box}body{margin:0;background:#0b1020;color:#f8fafc;font:15px system-ui,-apple-system,Segoe UI,sans-serif}
    main{max-width:1180px;margin:auto;padding:28px 20px}
    a{color:#93c5fd}h1{font-size:27px;margin:14px 0 8px}p{color:#94a3b8;line-height:1.5}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin-top:24px}
    .item{border:1px solid #334155;background:#151b2e;border-radius:15px;overflow:hidden}
    .item img{display:block;width:100%;height:220px;object-fit:contain;background:#070c17}
    .item p{padding:0 14px;color:#cbd5e1;font-size:12px;overflow-wrap:anywhere}
    .notice{border:1px solid #475569;border-radius:12px;padding:15px;margin-top:22px}
  </style>
</head>
<body><main>
  <a href="{{ route('cockpit.index') }}">Voltar ao Cockpit</a>
  <h1>Centro IA · Imagens geradas</h1>
  <p>Homologação isolada PR #122. Imagens consultadas diretamente em volume privado de somente leitura, sem novas chamadas à Roteia.</p>
  @if(!$available)
    <p class="notice">Volume de imagens não está disponível neste ambiente. A integração não está pronta para homologação.</p>
  @elseif(empty($images))
    <p class="notice">Nenhuma imagem PNG, JPEG ou WebP válida foi encontrada.</p>
  @else
    <div class="grid">
    @foreach($images as $image)
      <article class="item">
        <a href="{{ route('cockpit.centro-ia.image', ['year'=>$image['year'], 'month'=>$image['month'], 'day'=>$image['day'], 'filename'=>$image['filename']]) }}" target="_blank" rel="noopener noreferrer">
          <img src="{{ route('cockpit.centro-ia.image', ['year'=>$image['year'], 'month'=>$image['month'], 'day'=>$image['day'], 'filename'=>$image['filename']]) }}" alt="Imagem gerada no Centro IA" loading="lazy">
        </a>
        <p>{{ $image['filename'] }} · {{ number_format($image['bytes'] / 1024, 0, ',', '.') }} KB</p>
      </article>
    @endforeach
    </div>
  @endif
</main></body></html>
