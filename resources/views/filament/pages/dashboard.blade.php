<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/atlas-dashboard.css') }}?v=10.1.1">

    <div class="atlas-wrap">
        <div class="atlas-top">
            <div class="atlas-search">Pesquisar clientes, licenças, produtos, cobranças e projetos...</div>
            <div class="atlas-top-actions">
                <div class="atlas-pill">● Operação saudável</div>
                <a class="atlas-btn" href="/admin/factory-studio-enterprise">+ Novo</a>
            </div>
        </div>

        <section class="atlas-hero">
            <div class="atlas-hero-content">
                <div>
                    <div class="atlas-eyebrow">Centro Operacional Inteligente</div>
                    <h1>Central de Comando</h1>
                    <p>
                        Bom dia, Cristian. Sua operação está funcionando normalmente. Há duas licenças vencendo
                        nos próximos dias e três oportunidades comerciais aguardando retorno.
                    </p>
                </div>
                <div class="atlas-pulse">
                    <div class="atlas-eyebrow">Pulso da operação</div>
                    <strong>92%</strong>
                    <div class="atlas-bar"><span></span></div>
                    <p>Infraestrutura, IA, clientes, financeiro e publicações em situação estável.</p>
                </div>
            </div>
        </section>

        <section class="atlas-kpis">
            <div class="atlas-kpi cyan"><div class="label">Clientes ativos</div><div class="num">148</div><small>+8 este mês</small></div>
            <div class="atlas-kpi"><div class="label">Licenças</div><div class="num">186</div><small>3 vencem em 7 dias</small></div>
            <div class="atlas-kpi green"><div class="label">Receita mensal</div><div class="num">R$ 52k</div><small>+12% previsto</small></div>
            <div class="atlas-kpi violet"><div class="label">Agentes IA</div><div class="num">12</div><small>em operação</small></div>
            <div class="atlas-kpi amber"><div class="label">Projetos Factory</div><div class="num">{{ $this->countProjects() }}</div><small>2 em homologação</small></div>
        </section>

        <section class="atlas-grid">
            <div class="atlas-panel">
                <div class="atlas-panel-head">
                    <div><h2>Mapa do Ecossistema</h2><p>Produtos ativos, implantação e maturidade operacional.</p></div>
                    <a class="atlas-pill" href="/admin/products">Ver produtos</a>
                </div>
                <div class="atlas-ecosystem">
                    <div class="atlas-donut"><div><strong>86%</strong><span>base ativa</span></div></div>
                    <div class="atlas-legend">
                        @foreach ($this->getProducts() as $product)
                            <div class="atlas-legend-row"><span>{{ $product['name'] }}</span><b>{{ $product['progress'] }}%</b></div>
                            <div class="atlas-progress"><span style="width: {{ $product['progress'] }}%"></span></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="atlas-panel">
                <div class="atlas-panel-head"><div><h2>Fluxo Operacional</h2><p>Da venda à publicação.</p></div></div>
                <div class="atlas-activity">
                    @foreach ([
                        ['icon' => '01', 'title' => 'Lead comercial', 'desc' => 'Entrada pelo site ou atendimento'],
                        ['icon' => '02', 'title' => 'Cliente e licença', 'desc' => 'Plano, produto e valor vinculados'],
                        ['icon' => '03', 'title' => 'Fábrica IA', 'desc' => 'Projeto base, módulos e instalação'],
                        ['icon' => '04', 'title' => 'Homologação', 'desc' => 'Teste, ajuste e publicação'],
                    ] as $item)
                        <div class="atlas-step"><div class="atlas-ic">{{ $item['icon'] }}</div><div><b>{{ $item['title'] }}</b><span>{{ $item['desc'] }}</span></div><small>ativo</small></div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="atlas-bottom">
            <div class="atlas-panel">
                <h2>Agentes de IA</h2><p>Controle operacional dos agentes.</p>
                <div class="atlas-cards-mini">
                    <div class="atlas-mini"><b>IA Comercial</b><small>Online · 42 conversas</small></div>
                    <div class="atlas-mini"><b>IA Factory</b><small>Online · 3 builds</small></div>
                    <div class="atlas-mini"><b>IA QA</b><small>Online · 2 revisões</small></div>
                    <div class="atlas-mini"><b>IA Suporte</b><small>Online · 5 chamados</small></div>
                </div>
            </div>

            <div class="atlas-panel">
                <h2>Saúde da Plataforma</h2><p>Serviços principais.</p>
                <div class="atlas-legend">
                    <div class="atlas-legend-row"><span>Docker / PHP</span><b>100%</b></div><div class="atlas-progress"><span style="width:100%"></span></div>
                    <div class="atlas-legend-row"><span>MariaDB / Redis</span><b>100%</b></div><div class="atlas-progress"><span style="width:100%"></span></div>
                    <div class="atlas-legend-row"><span>GitHub Deploy</span><b>80%</b></div><div class="atlas-progress"><span style="width:80%"></span></div>
                </div>
            </div>

            <div class="atlas-panel">
                <h2>Próximas ações</h2><p>Orientação executiva.</p>
                <div class="atlas-activity">
                    <div class="atlas-step"><div class="atlas-ic">!</div><div><b>Renovar 2 licenças</b><span>Vencimento nos próximos 7 dias</span></div><small>prioridade</small></div>
                    <div class="atlas-step"><div class="atlas-ic">$</div><div><b>Enviar 3 propostas</b><span>Leads aguardando retorno</span></div><small>comercial</small></div>
                </div>
            </div>
        </section>

        <section class="atlas-panel" style="margin-top:14px">
            <div class="atlas-panel-head"><div><h2>Licenças recentes</h2><p>Exemplo de tabela Enterprise compacta.</p></div><a class="atlas-btn" href="/admin/licenses/create">Nova Licença</a></div>
            <div class="atlas-table">
                <div class="atlas-tr head"><div>Cliente</div><div>Produto</div><div>Plano</div><div>Situação</div></div>
                <div class="atlas-tr"><div>Prefeitura Modelo</div><div>TV Digital Enterprise</div><div>Enterprise</div><div><span class="atlas-badge">Ativa</span></div></div>
                <div class="atlas-tr"><div>Conheça Cidade</div><div>Guia Digital da Cidade</div><div>Start</div><div><span class="atlas-badge">Ativa</span></div></div>
                <div class="atlas-tr"><div>AssessorGov IA</div><div>GovTech</div><div>Premium</div><div><span class="atlas-badge">Homologação</span></div></div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
