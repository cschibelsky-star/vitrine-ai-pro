<x-filament-panels::page>
    <div class="vai-shell">
        <section class="vai-hero"><span class="vai-kicker">Marketplace Enterprise</span><h1>Produtos Estratégicos</h1><p>Catálogo interno dos produtos oficiais da Vitrine AI Pro para instalação, licenciamento, evolução e demonstração.</p></section>
        <div class="vai-grid vai-grid-3">
            @foreach([
                ['Consultor AI GOV360','Assistente de licitações e vendas governamentais para pequenas empresas.','Homologação'],
                ['Guia Digital da Cidade','Plataforma turística/comercial licenciável por cidade.','Pronto para venda'],
                ['TV Digital Enterprise','Portal de notícias, vídeos, RSS, IA editorial e transmissão ao vivo.','Pronto para venda'],
                ['Portal News AI','Portal automatizado para órgãos públicos, mídia local e comunicação institucional.','Roadmap'],
                ['Município Digital IA','Base modular para serviços digitais municipais.','Roadmap'],
                ['SISMED','Gestão de saúde em desenvolvimento progressivo.','Em desenvolvimento'],
            ] as $product)
                <div class="vai-card vai-product-card"><div><div class="vai-product-tag">Produto estratégico</div><div class="vai-product-title">{{ $product[0] }}</div><p class="vai-muted">{{ $product[1] }}</p></div><div><span class="vai-status">● {{ $product[2] }}</span><div style="margin-top:14px"><a class="vai-btn" href="#">Ver produto</a></div></div></div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
