<x-filament-panels::page>
    @php
        $data = $this->getDashboardData();
        $runtime = (array) ($data['runtime'] ?? []);
        $agents = (array) ($data['agents'] ?? []);
        $pipeline = (array) ($data['pipeline'] ?? []);
        $state = (array) ($data['state'] ?? []);
        $campaign = is_array($state['campaign'] ?? null) ? $state['campaign'] : null;
        $tasks = (array) ($state['tasks'] ?? []);
        $statusCounts = (array) ($state['status_counts'] ?? []);
        $blockedTasks = (array) ($state['blocked_tasks'] ?? []);
        $enabledAgents = collect($agents)->filter(fn (array $agent) => (bool) ($agent['enabled'] ?? false))->count();
    @endphp

    <div class="space-y-6">
        <section class="rounded-2xl border border-gray-800 bg-gray-950 p-6 text-white shadow-sm">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <div class="text-xs font-semibold uppercase tracking-[0.22em] text-primary-300">Core · IA Center · Control Plane</div>
                    <h1 class="mt-3 text-3xl font-bold">Marketing IA</h1>
                    <p class="mt-2 text-sm leading-6 text-gray-300">
                        Visão operacional do Marketing Agents Core. O estado é lido do serviço de Marketing pela rede interna; o Core não replica a persistência da campanha.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-xl border border-gray-800 bg-gray-900/80 p-4">
                        <div class="text-xs uppercase tracking-wider text-gray-400">Serviço</div>
                        <div class="mt-2 font-bold">{{ ($data['available'] ?? false) ? 'conectado' : 'indisponível' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-800 bg-gray-900/80 p-4">
                        <div class="text-xs uppercase tracking-wider text-gray-400">Agentes</div>
                        <div class="mt-2 text-2xl font-bold">{{ count($agents) ?: '—' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-800 bg-gray-900/80 p-4">
                        <div class="text-xs uppercase tracking-wider text-gray-400">Habilitados</div>
                        <div class="mt-2 text-2xl font-bold">{{ count($agents) ? $enabledAgents : '—' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-800 bg-gray-900/80 p-4">
                        <div class="text-xs uppercase tracking-wider text-gray-400">Artefatos</div>
                        <div class="mt-2 text-2xl font-bold">{{ $state['artifact_count'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </section>

        @if (! ($data['available'] ?? false))
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-900/60 dark:bg-amber-950/20">
                <div class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">Integração interna</div>
                <h2 class="mt-2 font-bold text-amber-950 dark:text-amber-100">Marketing Agents Core não respondeu</h2>
                <p class="mt-2 text-sm text-amber-800 dark:text-amber-300">Motivo técnico: {{ $data['reason'] ?? 'unknown' }}</p>
            </section>
        @else
            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Campanha observada</div>
                    <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $campaign['name'] ?? $campaign['public_id'] ?? 'Sem campanha persistida' }}</div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Status: {{ $campaign['status'] ?? '—' }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Gemini</div>
                    <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ ($runtime['gemini_configured'] ?? false) ? 'Configurado' : 'Não configurado' }}</div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $runtime['model'] ?? '—' }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Aprovação</div>
                    <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">{{ $runtime['approval_mode'] ?? '—' }}</div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Publicação e gasto permanecem bloqueados na V1.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">Bloqueios</div>
                    <div class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">{{ count($blockedTasks) }}</div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tarefas atualmente bloqueadas.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-primary-600">Workflow real</div>
                        <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">Pipeline da campanha</h2>
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Fonte: Marketing Agents Core</span>
                </div>

                <div class="mt-6 grid gap-3 lg:grid-cols-6">
                    @foreach ($pipeline as $stage)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950/50">
                            <div class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $stage['label'] ?? 'Etapa' }}</div>
                            <div class="mt-3 space-y-3">
                                @foreach ((array) ($stage['agents'] ?? []) as $agentId)
                                    @php
                                        $agent = $agents[$agentId] ?? [];
                                        $task = $tasks[$agentId] ?? [];
                                    @endphp
                                    <div>
                                        <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $agent['name'] ?? $agentId }}</div>
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $task['status'] ?? 'sem execução' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-semibold uppercase tracking-wider text-primary-600">Estado</div>
                    <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">Tarefas por status</h2>
                    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @forelse ($statusCounts as $status => $count)
                            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                                <div class="text-xs uppercase tracking-wider text-gray-500">{{ $status }}</div>
                                <div class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $count }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma campanha persistida ainda.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-semibold uppercase tracking-wider text-primary-600">Governança</div>
                    <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">Controles V1</h2>
                    <div class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <div>Publicação automática: <strong>não permitida</strong></div>
                        <div>Gasto de mídia: <strong>não permitido</strong></div>
                        <div>Modo de aprovação: <strong>{{ $runtime['approval_mode'] ?? '—' }}</strong></div>
                        <div>Schema: <strong>{{ $runtime['schema_version'] ?? '—' }}</strong></div>
                    </div>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
