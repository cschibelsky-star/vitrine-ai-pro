<x-filament-panels::page>
    @php
        $agents = $this->getAgents();
        $runtime = $this->getRuntime();
        $pipeline = $this->getPipeline();
        $campaignState = $this->getCampaignState();
        $campaign = $campaignState['campaign'] ?? null;
        $tasks = $campaignState['tasks'] ?? [];
        $enabledAgents = collect($agents)->filter(fn (array $agent) => (bool) ($agent['enabled'] ?? false))->count();
        $blockingAgents = collect($agents)->filter(fn (array $agent) => (bool) ($agent['may_block_pipeline'] ?? false))->count();
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-2xl border border-gray-800 bg-gray-950 p-6 text-white shadow-sm">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <div class="text-xs font-semibold uppercase tracking-[0.22em] text-primary-300">Core · IA Center</div>
                    <h1 class="mt-3 text-3xl font-bold">Marketing IA</h1>
                    <p class="mt-2 text-sm leading-6 text-gray-300">
                        Painel operacional da equipe de agentes de Marketing da Vitrine IA Pro, conectado ao estado persistido das campanhas quando essa camada estiver disponível.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-gray-800 bg-gray-900/80 p-4">
                        <div class="text-xs uppercase tracking-wider text-gray-400">Agentes V1</div>
                        <div class="mt-2 text-2xl font-bold">{{ count($agents) }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-800 bg-gray-900/80 p-4">
                        <div class="text-xs uppercase tracking-wider text-gray-400">Habilitados</div>
                        <div class="mt-2 text-2xl font-bold">{{ $enabledAgents }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-800 bg-gray-900/80 p-4">
                        <div class="text-xs uppercase tracking-wider text-gray-400">Podem bloquear</div>
                        <div class="mt-2 text-2xl font-bold">{{ $blockingAgents }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-800 bg-gray-900/80 p-4">
                        <div class="text-xs uppercase tracking-wider text-gray-400">Estado observado</div>
                        <div class="mt-2 text-lg font-bold">{{ $campaign ? strtoupper($campaign['status']) : 'SEM CAMPANHA' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Gemini</div>
                <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $runtime['gemini_configured'] ? 'Configurado' : 'Não configurado' }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Modelo: {{ $runtime['model'] }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Estratégia Gemini</div>
                <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $runtime['strategy_enabled'] ? 'Habilitada' : 'Desabilitada' }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Feature flag do runtime.</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aprovação</div>
                <div class="mt-2 text-lg font-semibold capitalize text-gray-950 dark:text-white">{{ $runtime['approval_mode'] }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Publicação e gasto continuam bloqueados na V1.</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Contrato</div>
                <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">v{{ $runtime['schema_version'] }}</div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Schema do Marketing Agents Core.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-primary-600">Workflow</div>
                    <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">Pipeline da campanha</h2>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    @if ($campaign)
                        {{ $campaign['name'] ?: $campaign['public_id'] }} · {{ $campaign['status'] }}
                    @else
                        {{ ($campaignState['reason'] ?? '') === 'persistence_not_ready' ? 'Persistência ainda não disponível' : 'Nenhuma campanha persistida' }}
                    @endif
                </span>
            </div>

            <div class="mt-6 grid gap-3 lg:grid-cols-6">
                @foreach ($pipeline as $stage)
                    <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                        <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $stage['label'] }}</div>
                        <div class="mt-3 space-y-2">
                            @foreach ($stage['agents'] as $agentId)
                                @php
                                    $agent = $agents[$agentId] ?? null;
                                    $task = $tasks[$agentId] ?? null;
                                @endphp
                                @if ($agent)
                                    <div>
                                        <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $agent['name'] }}</div>
                                        <div class="mt-1 text-xs {{ $task ? 'text-primary-600' : (($agent['enabled'] ?? false) ? 'text-success-600' : 'text-gray-400') }}">
                                            {{ $task ? $task['status'] : (($agent['enabled'] ?? false) ? 'configurado' : 'condicional/inativo') }}
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <div class="xl:col-span-2 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-primary-600">Equipe</div>
                    <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">9 agentes registrados</h2>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach ($agents as $agentId => $agent)
                        <article class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-semibold text-gray-950 dark:text-white">{{ $agent['name'] }}</h3>
                                    <p class="mt-1 text-xs uppercase tracking-wider text-gray-400">{{ $agent['type'] }} · {{ $agentId }}</p>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ ($agent['enabled'] ?? false) ? 'bg-success-50 text-success-700 dark:bg-success-950/40 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                                    {{ ($agent['enabled'] ?? false) ? 'habilitado' : 'inativo' }}
                                </span>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <div>Publicar: <strong class="text-gray-700 dark:text-gray-200">não</strong></div>
                                <div>Gastar: <strong class="text-gray-700 dark:text-gray-200">não</strong></div>
                                <div>Bloqueia pipeline: <strong class="text-gray-700 dark:text-gray-200">{{ ($agent['may_block_pipeline'] ?? false) ? 'sim' : 'não' }}</strong></div>
                                <div>Versão: <strong class="text-gray-700 dark:text-gray-200">{{ $agent['version'] ?? '—' }}</strong></div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-primary-200 bg-primary-50 p-5 dark:border-primary-900/60 dark:bg-primary-950/20">
                    <div class="text-xs font-semibold uppercase tracking-wider text-primary-700 dark:text-primary-400">Campaign State</div>
                    <h2 class="mt-2 font-bold text-primary-950 dark:text-primary-100">{{ $campaign ? ($campaign['name'] ?: 'Campanha observada') : 'Sem campanha persistida' }}</h2>
                    @if ($campaign)
                        <div class="mt-3 space-y-1 text-sm text-primary-800 dark:text-primary-300">
                            <div>Status: <strong>{{ $campaign['status'] }}</strong></div>
                            <div>Tarefas: <strong>{{ count($tasks) }}</strong></div>
                            <div>Artefatos: <strong>{{ $campaignState['artifact_count'] }}</strong></div>
                            <div>Bloqueios: <strong>{{ count($campaignState['blocked_tasks'] ?? []) }}</strong></div>
                        </div>
                    @else
                        <p class="mt-2 text-sm leading-6 text-primary-800 dark:text-primary-300">
                            O dashboard não fabrica números: aguardará um registro real da camada de persistência do Marketing IA.
                        </p>
                    @endif
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Estados das tarefas</div>
                    @if ($campaign)
                        <div class="mt-3 space-y-2 text-sm">
                            @forelse (($campaignState['status_counts'] ?? []) as $status => $count)
                                <div class="flex justify-between"><span class="text-gray-500 dark:text-gray-400">{{ $status }}</span><strong class="text-gray-950 dark:text-white">{{ $count }}</strong></div>
                            @empty
                                <div class="text-gray-500">Nenhuma tarefa registrada.</div>
                            @endforelse
                        </div>
                    @else
                        <div class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">—</div>
                    @endif
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Consumo do período</div>
                    <div class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">—</div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Permanece sem estimativa até existir contabilização auditada de tokens e custo.</p>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
