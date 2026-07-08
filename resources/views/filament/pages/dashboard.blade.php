<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/vitrine-enterprise-ui.css') }}">

    <div class="vai-enterprise-shell vai-grid-glow">
        <section class="vai-topbar">
            <div>
                <div class="vai-eyebrow">Factory Enterprise 10.0 · Centro Operacional SaaS</div>
                <h1>Vitrine IA Pro<br>Command Center</h1>
                <p>
                    Painel executivo para operar clientes, licenças, produtos, agentes de IA, entregas SaaS,
                    Factory Studio e implantação dos módulos do ecossistema em uma única plataforma.
                </p>
            </div>

            <div class="vai-topbar-actions">
                <div class="vai-search">Buscar clientes, licenças, módulos e execuções...</div>
                <div class="vai-chip vai-chip-success">● VPS ativa</div>
                <a class="vai-button" href="/admin/factory-studio-enterprise">Abrir Factory Studio</a>
            </div>
        </section>

        <section class="vai-metrics-grid">
            @foreach ([
                ['label' => 'Produtos oficiais', 'value' => '4', 'trend' => 'Guia, TV, GovTech e SISMED', 'icon' => '◈', 'tone' => ''],
                ['label' => 'Projetos Factory', 'value' => $this->countProjects(), 'trend' => 'Blueprints prontos para produção', 'icon' => '⚙', 'tone' => 'vai-tone-purple'],
                ['label' => 'Pedidos comerciais', 'value' => $this->countCommercialIntakes(), 'trend' => 'Comercial conectado à entrega', 'icon' => '↗', 'tone' => 'vai-tone-green'],
                ['label' => 'Release operacional', 'value' => '10.0', 'trend' => 'Enterprise UI Pack RC1', 'icon' => '✦', 'tone' => 'vai-tone-orange'],
            ] as $card)
                <div class="vai-metric-card {{ $card['tone'] }}">
                    <div class="vai-metric-head">
                        <span>{{ $card['label'] }}</span>
                        <span class="vai-metric-icon">{{ $card['icon'] }}</span>
                    </div>
                    <strong>{{ $card['value'] }}</strong>
                    <small>{{ $card['trend'] }}</small>
                    <div class="vai-sparkline"><span></span></div>
                </div>
            @endforeach
        </section>

        <section class="vai-main-grid">
            <div class="vai-panel vai-panel-large">
                <div class="vai-panel-head">
                    <div>
                        <h2>Mapa do Ecossistema</h2>
                        <p>Status operacional dos produtos que compõem a Vitrine IA Pro.</p>
                    </div>
                    <a href="/admin/products">Gerenciar produtos</a>
                </div>

                <div class="vai-ecosystem">
                    <div class="vai-donut">
                        <div>
                            <strong>86%</strong>
                            <span>base operacional</span>
                        </div>
                    </div>

                    <div class="vai-legend">
                        @foreach ($this->getProducts() as $product)
                            <div>
                                <span>{{ $product['name'] }}</span>
                                <strong>{{ $product['status'] }}</strong>
                            </div>
                            <div class="vai-progress"><span style="width: {{ $product['progress'] }}%"></span></div>
                        @endforeach
                    </div>
                </div>

                <div class="vai-rings">
                    <div><span>Core</span><small>Licenças</small></div>
                    <div><span>IA</span><small>Agentes</small></div>
                    <div><span>SaaS</span><small>Produtos</small></div>
                    <div><span>Ops</span><small>Deploy</small></div>
                </div>
            </div>

            <div class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>Pipeline Enterprise</h2>
                        <p>Da venda à publicação.</p>
                    </div>
                </div>

                <div class="vai-activity-list">
                    @foreach ([
                        ['icon' => '01', 'title' => 'Lead comercial', 'desc' => 'Entrada pelo site ou atendimento'],
                        ['icon' => '02', 'title' => 'Cliente e licença', 'desc' => 'Plano, produto e valor vinculados'],
                        ['icon' => '03', 'title' => 'Factory Studio', 'desc' => 'Blueprint, módulos e instalação'],
                        ['icon' => '04', 'title' => 'Homologação', 'desc' => 'Teste, ajuste e publicação'],
                    ] as $item)
                        <div class="vai-activity">
                            <div class="vai-activity-icon">{{ $item['icon'] }}</div>
                            <div>
                                <strong>{{ $item['title'] }}</strong>
                                <span>{{ $item['desc'] }}</span>
                            </div>
                            <small>ativo</small>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>Ações rápidas</h2>
                        <p>Atalhos da operação.</p>
                    </div>
                </div>

                <div class="vai-quick-list">
                    <a href="/admin/companies">Clientes <span>→</span></a>
                    <a href="/admin/licenses">Licenças <span>→</span></a>
                    <a href="/admin/leads">Comercial <span>→</span></a>
                    <a href="/admin/ai-center-enterprise">IA Center <span>→</span></a>
                    <a href="/admin/generated-projects">Projetos <span>→</span></a>
                </div>
            </div>
        </section>

        <section class="vai-bottom-grid">
            <div class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>IA Operacional</h2>
                        <p>Agentes especializados.</p>
                    </div>
                </div>
                <div class="vai-ranking">
                    @foreach (['IA Comercial', 'IA Arquiteta', 'IA Desenvolvedora', 'IA QA', 'IA Deploy'] as $index => $agent)
                        <div><span>{{ $index + 1 }}</span><strong>{{ $agent }}</strong><small>online</small></div>
                    @endforeach
                </div>
            </div>

            <div class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>Saúde da Plataforma</h2>
                        <p>Infraestrutura e execução.</p>
                    </div>
                </div>
                @foreach ([
                    ['label' => 'Docker / PHP 8.3', 'value' => '100%'],
                    ['label' => 'MariaDB / Redis', 'value' => '100%'],
                    ['label' => 'GitHub Deploy', 'value' => '80%'],
                    ['label' => 'Backups automáticos', 'value' => '65%'],
                ] as $row)
                    <div class="vai-status-row"><span>{{ $row['label'] }}</span><div class="vai-progress"><span style="width: {{ $row['value'] }}"></span></div></div>
                @endforeach
            </div>

            <div class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>Próxima entrega</h2>
                        <p>Factory Enterprise 10.1</p>
                    </div>
                </div>
                <div class="vai-activity-list">
                    <div class="vai-activity">
                        <div class="vai-activity-icon">UI</div>
                        <div>
                            <strong>Padronização visual</strong>
                            <span>Aplicar o tema Enterprise em páginas, recursos e portal do cliente.</span>
                        </div>
                        <small>em construção</small>
                    </div>
                    <div class="vai-activity">
                        <div class="vai-activity-icon">DB</div>
                        <div>
                            <strong>Migrations consolidadas</strong>
                            <span>Eliminar duplicidades e estabilizar instalação limpa.</span>
                        </div>
                        <small>próximo</small>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
