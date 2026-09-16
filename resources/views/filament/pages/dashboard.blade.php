<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/atlas-dashboard.css') }}?v=10.2.0">
    @php($metrics = $this->metrics())

    <div class="atlas-wrap factory-cockpit">
        <div class="atlas-top">
            <div class="atlas-search">Factory · projetos, blueprints, capabilities e execuções</div>
            <div class="atlas-top-actions">
                <div class="atlas-pill">● Factory operacional</div>
                <a class="atlas-btn" href="/admin/factory-studio-enterprise">Abrir Studio</a>
            </div>
        </div>

        <section class="factory-shell">
            <div class="factory-orbit-panel">
                <div class="factory-orbit">
                    <div class="factory-core-node">
                        <span>Vitrine IA Pro</span>
                        <strong>FACTORY</strong>
                        <small>produção assistida</small>
                    </div>

                    @foreach ($this->stages() as $index => $stage)
                        <button type="button"
                            class="factory-stage {{ $index === 0 ? 'is-selected' : '' }} stage-{{ $index + 1 }}"
                            data-title="{{ $stage['title'] }}"
                            data-detail="{{ $stage['detail'] }} Os detalhes operacionais aparecerão aqui conforme existirem registros reais.">
                            <span>{{ $stage['code'] }}</span>
                            <b>{{ $stage['title'] }}</b>
                        </button>
                    @endforeach
                </div>
            </div>

            <aside class="factory-context-panel">
                <div class="factory-context-head">
                    <span class="factory-context-kicker">Contexto selecionado</span>
                    <h1 id="factory-context-title">Intake & Demandas</h1>
                    <p id="factory-context-detail">Necessidades registradas e entrada operacional. Os detalhes operacionais aparecerão aqui conforme existirem registros reais.</p>
                </div>

                <div class="factory-metrics-grid">
                    <div class="factory-metric"><span>Projetos</span><strong>{{ $metrics['projects'] }}</strong><small>registrados</small></div>
                    <div class="factory-metric"><span>Blueprints</span><strong>{{ $metrics['blueprints'] }}</strong><small>modelos técnicos</small></div>
                    <div class="factory-metric"><span>Capabilities</span><strong>{{ $metrics['capabilities'] }}</strong><small>recursos mapeados</small></div>
                    <div class="factory-metric"><span>Execuções</span><strong>{{ $metrics['executions'] }}</strong><small>histórico real</small></div>
                </div>

                <div class="factory-persistent">
                    <div><b>Fonte dos indicadores</b><span>Tabelas reais da Factory: projetos, blueprints, capabilities e execuções.</span></div>
                    <div><b>Execuções em andamento</b><span>{{ $metrics['running'] }} registro(s) com status running.</span></div>
                    <div><b>Publicação controlada</b><span>Deploy continua sujeito à homologação e confirmação.</span></div>
                </div>
            </aside>
        </section>

        <section class="factory-bottom-grid">
            <article class="factory-panel">
                <div class="factory-panel-head"><div><h2>Execuções recentes</h2><p>Últimos registros reais da Factory.</p></div><span class="atlas-pill">{{ $metrics['finished'] }} concluídas</span></div>
                <div class="factory-feed">
                    @forelse ($this->recentExecutions() as $execution)
                        <div class="factory-feed-row">
                            <div class="factory-feed-icon">EX</div>
                            <div><b>{{ $execution['name'] }}</b><span>{{ $execution['project'] }}</span></div>
                            <div class="factory-feed-status"><strong>{{ $execution['status'] }}</strong><small>{{ $execution['duration'] }}</small></div>
                        </div>
                    @empty
                        <div class="factory-empty">Nenhuma execução registrada.</div>
                    @endforelse
                </div>
            </article>

            <article class="factory-panel">
                <div class="factory-panel-head"><div><h2>Estado da produção</h2><p>Sem números demonstrativos.</p></div></div>
                <div class="factory-status-list">
                    <div><span>Em execução</span><strong>{{ $metrics['running'] }}</strong></div>
                    <div><span>Concluídas</span><strong>{{ $metrics['finished'] }}</strong></div>
                    <div><span>Falhas registradas</span><strong>{{ $metrics['failed'] }}</strong></div>
                    <div><span>Total de execuções</span><strong>{{ $metrics['executions'] }}</strong></div>
                </div>
            </article>
        </section>
    </div>

    <script>
        (() => {
            const stages = document.querySelectorAll('.factory-stage');
            const title = document.getElementById('factory-context-title');
            const detail = document.getElementById('factory-context-detail');
            stages.forEach((stage) => {
                stage.addEventListener('click', () => {
                    stages.forEach((item) => item.classList.remove('is-selected'));
                    stage.classList.add('is-selected');
                    title.textContent = stage.dataset.title || 'Factory';
                    detail.textContent = stage.dataset.detail || '';
                });
            });
        })();
    </script>
</x-filament-panels::page>
