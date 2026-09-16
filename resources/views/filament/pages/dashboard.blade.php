<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/atlas-dashboard.css') }}?v=10.2.0">
    @php($metrics = $this->metrics())

    <div class="atlas-wrap atlas-core">
        <div class="atlas-top">
            <div class="atlas-search">Centro Operacional · clientes, produtos, planos, licenças, módulos e IA</div>
            <div class="atlas-top-actions">
                <div class="atlas-pill">● Core operacional</div>
                <a class="atlas-btn" href="/admin/licenses/create">Nova licença</a>
            </div>
        </div>

        <section class="atlas-hero core-hero">
            <div class="atlas-hero-content">
                <div>
                    <div class="atlas-eyebrow">Vitrine IA Pro · Core</div>
                    <h1>Centro Operacional</h1>
                    <p>Gestão central do ecossistema com indicadores calculados diretamente dos registros atuais do Core.</p>
                </div>
                <div class="atlas-pulse core-pulse">
                    <div class="atlas-eyebrow">Base atual</div>
                    <strong>{{ $metrics['products'] }}</strong>
                    <p>produtos ativos, {{ $metrics['plans'] }} planos e {{ $metrics['modules'] }} módulos cadastrados.</p>
                </div>
            </div>
        </section>

        <section class="atlas-kpis">
            <div class="atlas-kpi violet"><div class="label">Empresas</div><div class="num">{{ $metrics['companies'] }}</div><small>{{ $metrics['active_companies'] }} ativas</small></div>
            <div class="atlas-kpi"><div class="label">Licenças</div><div class="num">{{ $metrics['licenses'] }}</div><small>{{ $metrics['active_licenses'] }} operacionais</small></div>
            <div class="atlas-kpi green"><div class="label">Valor licenciado</div><div class="num">R$ {{ number_format($metrics['licensed_value'], 0, ',', '.') }}</div><small>soma dos registros ativos/homologação/trial</small></div>
            <div class="atlas-kpi cyan"><div class="label">Agentes IA</div><div class="num">{{ $metrics['agents'] }}</div><small>registrados no Core</small></div>
            <div class="atlas-kpi amber"><div class="label">Módulos por empresa</div><div class="num">{{ $metrics['company_modules'] }}</div><small>vínculos materializados</small></div>
        </section>

        <section class="atlas-grid">
            <div class="atlas-panel">
                <div class="atlas-panel-head">
                    <div><h2>Mapa real do ecossistema</h2><p>Produtos e seus vínculos atuais.</p></div>
                    <a class="atlas-pill" href="/admin/products">Ver produtos</a>
                </div>
                <div class="atlas-product-grid">
                    @forelse ($this->getProducts() as $product)
                        <article class="atlas-product-card">
                            <div class="atlas-product-head"><b>{{ $product['name'] }}</b><span>{{ $product['status'] }}</span></div>
                            <div class="atlas-product-metrics">
                                <div><strong>{{ $product['plans'] }}</strong><small>planos</small></div>
                                <div><strong>{{ $product['licenses'] }}</strong><small>licenças</small></div>
                                <div><strong>{{ $product['modules'] }}</strong><small>módulos</small></div>
                            </div>
                        </article>
                    @empty
                        <div class="atlas-empty">Nenhum produto cadastrado.</div>
                    @endforelse
                </div>
            </div>

            <div class="atlas-panel">
                <div class="atlas-panel-head"><div><h2>Estado operacional</h2><p>Indicadores derivados da base atual.</p></div></div>
                <div class="atlas-activity">
                    <div class="atlas-step"><div class="atlas-ic">30</div><div><b>Licenças a vencer</b><span>Próximos 30 dias</span></div><small>{{ $this->expiringLicenses() }}</small></div>
                    <div class="atlas-step"><div class="atlas-ic">IM</div><div><b>Implantações</b><span>Em implantação ou homologação</span></div><small>{{ $this->implementationCompanies() }}</small></div>
                    <div class="atlas-step"><div class="atlas-ic">PL</div><div><b>Planos</b><span>Configurações comerciais cadastradas</span></div><small>{{ $metrics['plans'] }}</small></div>
                    <div class="atlas-step"><div class="atlas-ic">MD</div><div><b>Módulos</b><span>Catálogo técnico atual</span></div><small>{{ $metrics['modules'] }}</small></div>
                </div>
            </div>
        </section>

        <section class="atlas-panel" style="margin-top:14px">
            <div class="atlas-panel-head"><div><h2>Licenças recentes</h2><p>Registros reais da base do Core.</p></div><a class="atlas-btn" href="/admin/licenses">Abrir licenças</a></div>
            <div class="atlas-table">
                <div class="atlas-tr head"><div>Cliente</div><div>Produto</div><div>Plano</div><div>Situação</div></div>
                @forelse ($this->recentLicenses() as $license)
                    <div class="atlas-tr">
                        <div>{{ $license['company'] }}</div>
                        <div>{{ $license['product'] }}</div>
                        <div>{{ $license['plan'] }}</div>
                        <div><span class="atlas-badge">{{ $license['status'] }}</span></div>
                    </div>
                @empty
                    <div class="atlas-tr"><div>Sem licenças registradas</div></div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
