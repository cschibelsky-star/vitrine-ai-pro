<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/vitrine-master-enterprise.css') }}">

    <div class="vip-hero">
        <div>
            <span class="vip-kicker">Vitrine AI Pro Master Enterprise 2.0</span>
            <h1>Centro Operacional Inteligente</h1>
            <p>Visão executiva do ecossistema: clientes, produtos, licenças, comercial, financeiro, IA e Factory.</p>
        </div>
        <div class="vip-status">Sistema em homologação</div>
    </div>

    <div class="vip-grid vip-grid-4">
        <div class="vip-card"><span>Clientes ativos</span><strong>{{ \App\Models\Cliente::count() ?? 0 }}</strong><small>Base operacional</small></div>
        <div class="vip-card"><span>Produtos</span><strong>{{ \App\Models\Produto::count() ?? 0 }}</strong><small>Ecossistema SaaS</small></div>
        <div class="vip-card"><span>Licenças</span><strong>{{ \App\Models\Licenca::count() ?? 0 }}</strong><small>Contratos ativos</small></div>
        <div class="vip-card"><span>Leads</span><strong>{{ \App\Models\Lead::count() ?? 0 }}</strong><small>Pipeline comercial</small></div>
    </div>

    <div class="vip-grid vip-grid-2">
        <section class="vip-panel">
            <h2>Ações rápidas</h2>
            <div class="vip-actions">
                <a href="/admin/clientes">Clientes</a>
                <a href="/admin/produtos">Produtos</a>
                <a href="/admin/licencas">Licenças</a>
                <a href="/admin/leads">Comercial</a>
            </div>
        </section>
        <section class="vip-panel">
            <h2>Status operacional</h2>
            <ul class="vip-list">
                <li><span>Centro Operacional</span><b>Online</b></li>
                <li><span>Factory</span><b>Em evolução</b></li>
                <li><span>IA</span><b>Camada transversal</b></li>
                <li><span>Marketplace</span><b>Preparado</b></li>
            </ul>
        </section>
    </div>
</x-filament-panels::page>
