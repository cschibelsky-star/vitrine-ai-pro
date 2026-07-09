<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/vitrine-enterprise-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/vitrine-enterprise-overrides.css') }}">

    <div class="vai-enterprise-shell vai-grid-glow">
        <section class="vai-topbar">
            <div>
                <div class="vai-eyebrow">Centro Operacional Inteligente · Vitrine IA Pro Enterprise</div>
                <h1>Central de<br>Comando</h1>
                <p>
                    Bom dia, Cristian. Sua operação está funcionando normalmente. Há licenças para acompanhar,
                    projetos em evolução e agentes de IA em operação dentro do ecossistema.
                </p>
            </div>

            <div class="vai-topbar-actions">
                <div class="vai-search">Pesquisar clientes, licenças, produtos, cobranças e projetos...</div>
                <div class="vai-chip vai-chip-success">● Operação saudável</div>
                <a class="vai-button" href="/admin/factory-studio-enterprise">+ Novo Projeto</a>
            </div>
        </section>

        <section class="vai-metrics-grid vai-metrics-grid-compact">
            @foreach ([
                ['label' => 'Clientes ativos', 'value' => '148', 'trend' => '+8 este mês', 'icon' => '👥', 'tone' => 'vai-tone-cyan'],
                ['label' => 'Licenças', 'value' => '186', 'trend' => '3 vencem em 7 dias', 'icon' => '◈', 'tone' => ''],
                ['label' => 'Receita mensal', 'value' => 'R$ 52k', 'trend' => '+12% previsto', 'icon' => '↗', 'tone' => 'vai-tone-green'],
                ['label' => 'Agentes IA', 'value' => '12', 'trend' => 'em operação', 'icon' => '🤖', 'tone' => 'vai-tone-purple'],
                ['label' => 'Projetos Factory', 'value' => $this->countProjects(), 'trend' => '2 em homologação', 'icon' => '⚙', 'tone' => 'vai-tone-orange'],
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

        <section class="vai-main-grid vai-main-grid-atlas">
            <div class="vai-panel vai-panel-large">
                <div class="vai-panel-head">
                    <div>
                        <h2>Mapa do Ecossistema</h2>
                        <p>Produtos ativos, implantação e maturidade operacional.</p>
                    </div>
                    <a href="/admin/products">Ver produtos</a>
                </div>

                <div class="vai-ecosystem">
                    <div class="vai-donut">
                        <div>
                            <strong>86%</strong>
                            <span>base ativa</span>
                        </div>
                    </div>

                    <div class="vai-legend">
                        @foreach ($this->getProducts() as $product)
                            <div>
                                <span>{{ $product['name'] }}</span>
                                <strong>{{ $product['progress'] }}%</strong>
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
                        <h2>Fluxo Operacional</h2>
                        <p>Da venda à publicação.</p>
                    </div>
                </div>

                <div class="vai-activity-list">
                    @foreach ([
                        ['icon' => '01', 'title' => 'Lead comercial', 'desc' => 'Entrada pelo site ou atendimento'],
                        ['icon' => '02', 'title' => 'Cliente e licença', 'desc' => 'Plano, produto e valor vinculados'],
                        ['icon' => '03', 'title' => 'Fábrica IA', 'desc' => 'Projeto base, módulos e instalação'],
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
        </section>

        <section class="vai-bottom-grid">
            <div class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>Agentes de IA</h2>
                        <p>Controle operacional dos agentes.</p>
                    </div>
                </div>
                <div class="vai-quick-list vai-mini-grid">
                    @foreach (['IA Comercial' => '42 conversas', 'IA Factory' => '3 builds', 'IA QA' => '2 revisões', 'IA Suporte' => '5 chamados'] as $agent => $info)
                        <a href="/admin/ai-center-enterprise"><strong>{{ $agent }}</strong><span>{{ $info }}</span></a>
                    @endforeach
                </div>
            </div>

            <div class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>Saúde da Plataforma</h2>
                        <p>Serviços principais.</p>
                    </div>
                </div>
                @foreach ([
                    ['label' => 'Docker / PHP', 'value' => '100%'],
                    ['label' => 'MariaDB / Redis', 'value' => '100%'],
                    ['label' => 'GitHub Deploy', 'value' => '80%'],
                ] as $row)
                    <div class="vai-status-row"><span>{{ $row['label'] }}</span><div class="vai-progress"><span style="width: {{ $row['value'] }}"></span></div></div>
                @endforeach
            </div>

            <div class="vai-panel">
                <div class="vai-panel-head">
                    <div>
                        <h2>Próximas ações</h2>
                        <p>Orientação executiva.</p>
                    </div>
                </div>
                <div class="vai-activity-list">
                    <div class="vai-activity">
                        <div class="vai-activity-icon">!</div>
                        <div>
                            <strong>Renovar 2 licenças</strong>
                            <span>Vencimento nos próximos 7 dias</span>
                        </div>
                        <small>prioridade</small>
                    </div>
                    <div class="vai-activity">
                        <div class="vai-activity-icon">$</div>
                        <div>
                            <strong>Enviar 3 propostas</strong>
                            <span>Leads aguardando retorno</span>
                        </div>
                        <small>comercial</small>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
