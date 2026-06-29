<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/vitrine-enterprise-ui.css') }}?v=20201">

    <div class="vai-enterprise-shell">
        <div class="vai-topbar">
            <div>
                <div class="vai-eyebrow">Vitrine AI Pro Master</div>
                <h1>Dashboard Executivo</h1>
                <p>Visão geral inteligente do ecossistema Vitrine AI Pro.</p>
            </div>

            <div class="vai-topbar-actions">
                <div class="vai-search">🔎 Buscar no sistema...</div>
                <div class="vai-chip">📅 {{ now()->format('d/m/Y') }}</div>
                <div class="vai-chip">🕒 {{ now()->format('H:i') }}</div>
                <div class="vai-chip vai-chip-success">Produção</div>
                <button class="vai-button">Atualizar</button>
            </div>
        </div>

        <div class="vai-metrics-grid">
            @foreach ($this->getExecutiveMetrics() as $metric)
                <div class="vai-metric-card vai-tone-{{ $metric['tone'] }}">
                    <div class="vai-metric-head">
                        <span>{{ $metric['label'] }}</span>
                        <div class="vai-metric-icon">{{ $metric['icon'] }}</div>
                    </div>
                    <strong>{{ $metric['value'] }}</strong>
                    <small>{{ $metric['hint'] }}</small>
                    <div class="vai-sparkline"><span></span></div>
                </div>
            @endforeach
        </div>

        <div class="vai-main-grid">
            <section class="vai-panel vai-panel-large">
                <div class="vai-panel-head">
                    <div>
                        <h2>Visão Geral do Ecossistema</h2>
                        <p>Distribuição estratégica das licenças e produtos.</p>
                    </div>
                    <a href="/admin/licenses">Ver relatório</a>
                </div>

                <div class="vai-ecosystem">
                    <div class="vai-donut">
                        <div>
                            <strong>{{ $this->metric('licenses', $this->metric('licencas', '25')) }}</strong>
                            <span>Total Licenças</span>
                        </div>
                    </div>
                    <div class="vai-legend">
                        @foreach ($this->getPopularProducts() as $product)
                            <div>
                                <span>{{ $product['name'] }}</span>
                                <strong>{{ $product['value'] }}%</strong>
                            </div>
                            <div class="vai-progress"><span style="width: {{ $product['value'] }}%"></span></div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>Atividades Recentes</h2>
                        <p>Últimos movimentos operacionais.</p>
                    </div>
                </div>

                <div class="vai-activity-list">
                    @foreach ($this->getActivities() as $activity)
                        <div class="vai-activity">
                            <div class="vai-activity-icon">{{ $activity['icon'] }}</div>
                            <div>
                                <strong>{{ $activity['title'] }}</strong>
                                <span>{{ $activity['detail'] }}</span>
                            </div>
                            <small>{{ $activity['time'] }}</small>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>Acesso Rápido</h2>
                        <p>Ações principais da operação.</p>
                    </div>
                </div>

                <div class="vai-quick-list">
                    <a href="/admin/companies">👥 Novo Cliente <span>+</span></a>
                    <a href="/admin/licenses">🛡️ Nova Licença <span>+</span></a>
                    <a href="/admin/leads">💼 Novo Lead <span>+</span></a>
                    <a href="/admin/factory-projects">🏭 Novo Projeto <span>+</span></a>
                    <a href="/admin/factory-dashboard">📊 Factory Core <span>→</span></a>
                </div>
            </section>
        </div>

        <div class="vai-bottom-grid">
            <section class="vai-panel">
                <h2>Performance do Sistema</h2>
                <div class="vai-rings">
                    <div><span>23%</span><small>CPU</small></div>
                    <div><span>41%</span><small>RAM</small></div>
                    <div><span>67%</span><small>IA</small></div>
                    <div><span>12%</span><small>Filas</small></div>
                </div>
            </section>

            <section class="vai-panel">
                <h2>Leads por Status</h2>
                @foreach ([['Novos', 90], ['Em Contato', 68], ['Proposta', 46], ['Negociação', 30], ['Fechado', 22]] as [$label, $value])
                    <div class="vai-status-row">
                        <span>{{ $label }}</span>
                        <div class="vai-progress"><span style="width: {{ $value }}%"></span></div>
                    </div>
                @endforeach
            </section>

            <section class="vai-panel">
                <h2>Produtos Mais Populares</h2>
                <div class="vai-ranking">
                    @foreach ($this->getPopularProducts() as $index => $product)
                        <div>
                            <span>{{ $index + 1 }}</span>
                            <strong>{{ $product['name'] }}</strong>
                            <small>{{ $product['value'] }}%</small>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-filament-panels::page>
