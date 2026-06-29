<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/vitrine-enterprise-real.css') }}?v=203">
    <div class="vip-shell">
        <section class="vip-hero">
            <div>
                <span class="vip-kicker">Factory 2.0</span>
                <h1>Ambiente de Construção</h1>
                <p>Studio, projetos, blueprints, capabilities, execuções e logs reunidos em uma única área operacional.</p>
            </div>
            <div class="vip-hero-actions">
                <a class="vip-btn" href="{{ url('/admin/factory-studio') }}">Abrir Studio</a>
                <a class="vip-btn" href="{{ url('/admin/marketplace-enterprise') }}">Marketplace</a>
            </div>
        </section>
        <section class="vip-metrics">
            @foreach ([['Projetos', $stats['projects'] ?? 0, 'ativos na factory', '📁', 'blue'], ['Capabilities', $stats['capabilities'] ?? 0, 'recursos disponíveis', '⚡', 'green'], ['Blueprints', $stats['blueprints'] ?? 0, 'modelos técnicos', '📄', 'purple'], ['Execuções', $stats['executions'] ?? 0, 'builds e rotinas', '▶', 'orange']] as $m)
                <article class="vip-card vip-metric vip-{{ $m[4] }}"><div><span>{{ $m[0] }}</span><strong>{{ $m[1] }}</strong><small>{{ $m[2] }}</small></div><div class="vip-icon">{{ $m[3] }}</div><div class="vip-spark"><i></i><i></i><i></i><i></i><i></i><i></i></div></article>
            @endforeach
        </section>
        <section class="vip-grid vip-grid-main">
            <article class="vip-card vip-panel"><div class="vip-panel-head"><div><h2>Pipeline Factory</h2><p>Fluxo técnico para gerar produtos SaaS com controle de qualidade.</p></div><span class="vip-badge">Online</span></div><div class="vip-timeline">@foreach(['Intake','Arquitetura','Blueprint','Build','QA','Deploy'] as $step)<div class="vip-timeline-row"><div class="vip-dot"></div><div><strong>{{ $step }}</strong><span>Etapa monitorada</span></div><em>OK</em></div>@endforeach</div></article>
            <article class="vip-card vip-panel"><div class="vip-panel-head"><h2>Agentes da Factory</h2></div><div class="vip-actions-list">@foreach(['IA Arquiteta','IA Desenvolvedora','IA QA','IA Deploy'] as $agent)<div class="vip-action"><div><strong>{{ $agent }}</strong><span>Disponível para execução</span></div><em>●</em></div>@endforeach</div></article>
            <article class="vip-card vip-panel"><div class="vip-panel-head"><h2>Ações Rápidas</h2></div><div class="vip-actions-list"><a class="vip-action" href="{{ url('/admin/factory-studio') }}"><div><strong>Novo Projeto</strong><span>Iniciar projeto Factory</span></div><em>+</em></a><a class="vip-action" href="{{ url('/admin/factory-blueprints') }}"><div><strong>Blueprints</strong><span>Ver modelos técnicos</span></div><em>↗</em></a><a class="vip-action" href="{{ url('/admin/factory-executions') }}"><div><strong>Execuções</strong><span>Histórico de builds</span></div><em>▶</em></a></div></article>
        </section>
    </div>
</x-filament-panels::page>
