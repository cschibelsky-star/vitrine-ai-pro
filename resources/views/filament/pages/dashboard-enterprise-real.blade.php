<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/vitrine-enterprise-real.css') }}?v=203">

    <div class="vip-shell">
        <section class="vip-hero">
            <div>
                <span class="vip-kicker">Vitrine AI Pro Master</span>
                <h1>Dashboard Executivo</h1>
                <p>Visão geral inteligente do ecossistema Vitrine AI Pro: clientes, produtos, licenças, financeiro, comercial e Factory.</p>
            </div>
            <div class="vip-hero-actions">
                <div class="vip-pill"><span>Data</span><strong>{{ now()->format('d/m/Y') }}</strong></div>
                <div class="vip-pill"><span>Hora</span><strong>{{ now()->format('H:i') }}</strong></div>
                <div class="vip-pill vip-online"><span>Ambiente</span><strong>Produção</strong></div>
                <a class="vip-btn" href="{{ url('/admin') }}">Atualizar</a>
            </div>
        </section>

        <section class="vip-metrics">
            @foreach ($metrics as $metric)
                <article class="vip-card vip-metric vip-{{ $metric['tone'] }}">
                    <div>
                        <span>{{ $metric['label'] }}</span>
                        <strong>{{ $metric['value'] }}</strong>
                        <small>{{ $metric['caption'] }}</small>
                    </div>
                    <div class="vip-icon">{{ $metric['icon'] }}</div>
                    <div class="vip-spark"><i></i><i></i><i></i><i></i><i></i><i></i></div>
                </article>
            @endforeach
        </section>

        <section class="vip-grid vip-grid-main">
            <article class="vip-card vip-panel">
                <div class="vip-panel-head">
                    <div>
                        <h2>Visão Geral do Ecossistema</h2>
                        <p>Produtos estratégicos e distribuição operacional das licenças.</p>
                    </div>
                    <span class="vip-badge">SaaS Enterprise</span>
                </div>
                <div class="vip-ecosystem">
                    <div class="vip-donut"><span>{{ $metrics[2]['value'] }}</span><small>Total licenças</small></div>
                    <div class="vip-list">
                        @foreach ($products as $index => $product)
                            <div class="vip-list-row">
                                <span><b>{{ $index + 1 }}</b>{{ $product }}</span>
                                <em>{{ max(2, 10 - $index * 2) }} licenças</em>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>

            <article class="vip-card vip-panel">
                <div class="vip-panel-head">
                    <div>
                        <h2>Atividades Recentes</h2>
                        <p>Movimentos importantes da operação.</p>
                    </div>
                </div>
                <div class="vip-timeline">
                    @foreach ($activity as $item)
                        <div class="vip-timeline-row">
                            <div class="vip-dot"></div>
                            <div><strong>{{ $item['title'] }}</strong><span>{{ $item['time'] }}</span></div>
                            <em>{{ $item['value'] }}</em>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="vip-card vip-panel">
                <div class="vip-panel-head"><h2>Acesso Rápido</h2></div>
                <div class="vip-actions-list">
                    @foreach ($quickActions as $action)
                        <a href="{{ url($action['url']) }}" class="vip-action">
                            <div><strong>{{ $action['label'] }}</strong><span>{{ $action['caption'] }}</span></div>
                            <em>{{ $action['icon'] }}</em>
                        </a>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="vip-grid vip-grid-bottom">
            <article class="vip-card vip-panel">
                <div class="vip-panel-head"><h2>Performance do Sistema</h2><span class="vip-badge vip-green">Online</span></div>
                <div class="vip-rings">
                    <div class="vip-ring"><strong>23%</strong><span>CPU</span></div>
                    <div class="vip-ring"><strong>41%</strong><span>Memória</span></div>
                    <div class="vip-ring"><strong>67%</strong><span>IA</span></div>
                    <div class="vip-ring"><strong>12%</strong><span>Queue</span></div>
                </div>
            </article>

            <article class="vip-card vip-panel">
                <div class="vip-panel-head"><h2>Leads por Status</h2></div>
                @foreach ([['Novos', 85], ['Em contato', 62], ['Proposta', 44], ['Negociação', 31], ['Fechado', 20]] as $bar)
                    <div class="vip-bar-row"><span>{{ $bar[0] }}</span><div><i style="width: {{ $bar[1] }}%"></i></div><em>{{ $bar[1] }}%</em></div>
                @endforeach
            </article>

            <article class="vip-card vip-panel">
                <div class="vip-panel-head"><h2>Produtos Mais Populares</h2></div>
                @foreach ($products as $index => $product)
                    <div class="vip-rank"><span>{{ $index + 1 }}</span><strong>{{ $product }}</strong><em>{{ max(8, 40 - $index * 8) }}%</em></div>
                @endforeach
            </article>
        </section>
    </div>
</x-filament-panels::page>
