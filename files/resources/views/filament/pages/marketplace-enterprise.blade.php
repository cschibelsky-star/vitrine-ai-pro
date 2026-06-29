<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/vitrine-enterprise-real.css') }}?v=203">
    <div class="vip-shell">
        <section class="vip-hero">
            <div>
                <span class="vip-kicker">Marketplace Enterprise</span>
                <h1>Produtos Homologados</h1>
                <p>Loja interna de produtos estratégicos, templates, módulos e soluções licenciáveis da Vitrine AI Pro.</p>
            </div>
            <div class="vip-hero-actions"><div class="vip-pill vip-online"><span>Status</span><strong>Catálogo ativo</strong></div></div>
        </section>
        <section class="vip-grid vip-grid-main">
            @foreach ([['Consultor AI GOV360','Assistente de licitações e venda governamental para pequenas empresas.','Estratégico'],['Guia Digital da Cidade','Plataforma replicável para turismo, comércio e cidade digital.','SaaS'],['TV Digital Enterprise','Portal TV com notícias, vídeos, RSS, ao vivo e IA editorial.','Enterprise']] as $product)
                <article class="vip-card vip-panel"><div class="vip-panel-head"><div><h2>{{ $product[0] }}</h2><p>{{ $product[1] }}</p></div><span class="vip-badge">{{ $product[2] }}</span></div><div class="vip-actions-list"><a class="vip-action" href="#"><div><strong>Preparar publicação</strong><span>Homologação comercial e técnica</span></div><em>↗</em></a><a class="vip-action" href="#"><div><strong>Licenciar</strong><span>Gerar proposta ou licença</span></div><em>+</em></a></div></article>
            @endforeach
        </section>
    </div>
</x-filament-panels::page>
