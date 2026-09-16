<x-filament-panels::page>
    <style>
        :root {
            --vitrine-bg: #090716;
            --vitrine-bg-2: #120a2d;
            --vitrine-panel: rgba(19, 14, 45, 0.92);
            --vitrine-panel-2: rgba(28, 18, 67, 0.86);
            --vitrine-border: rgba(139, 92, 246, 0.28);
            --vitrine-purple: #8b5cf6;
            --vitrine-violet: #6d28d9;
            --vitrine-magenta: #d946ef;
            --vitrine-text: #f8f7ff;
            --vitrine-muted: #b9b4d6;
        }

        body,
        .fi-body,
        .fi-main,
        .fi-layout {
            background:
                radial-gradient(circle at 18% 12%, rgba(124, 58, 237, 0.22), transparent 34%),
                radial-gradient(circle at 82% 8%, rgba(217, 70, 239, 0.12), transparent 30%),
                linear-gradient(180deg, var(--vitrine-bg-2) 0%, var(--vitrine-bg) 48%, #06050f 100%) !important;
            color: var(--vitrine-text) !important;
        }

        .fi-sidebar {
            background: linear-gradient(180deg, #1a0f3d 0%, #110927 48%, #0a0718 100%) !important;
            border-right: 1px solid rgba(139, 92, 246, 0.22) !important;
            box-shadow: 16px 0 48px rgba(27, 15, 69, 0.32) !important;
        }

        .fi-sidebar-header,
        .fi-topbar {
            background: rgba(12, 8, 29, 0.88) !important;
            border-color: rgba(139, 92, 246, 0.2) !important;
            backdrop-filter: blur(18px);
        }

        .fi-sidebar-item a,
        .fi-sidebar-item button {
            color: #d9d5ee !important;
            border-radius: 14px !important;
        }

        .fi-sidebar-item.fi-active a,
        .fi-sidebar-item a:hover,
        .fi-sidebar-item button:hover {
            background: linear-gradient(135deg, #7c3aed 0%, #8b5cf6 55%, #6d28d9 100%) !important;
            color: white !important;
            box-shadow: 0 10px 30px rgba(124, 58, 237, 0.28) !important;
        }

        .fi-sidebar-header .fi-logo,
        .fi-logo {
            color: #ffffff !important;
            font-weight: 800 !important;
            letter-spacing: -0.02em !important;
        }

        .marketing-ai-shell {
            color: var(--vitrine-text);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .marketing-ai-shell section,
        .marketing-ai-shell article,
        .marketing-ai-shell .vitrine-card {
            background: linear-gradient(145deg, rgba(30, 23, 65, 0.92), rgba(15, 12, 34, 0.96)) !important;
            border-color: var(--vitrine-border) !important;
            box-shadow: 0 18px 48px rgba(5, 3, 16, 0.34) !important;
        }

        .marketing-ai-shell section:first-of-type {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(circle at 78% 20%, rgba(168, 85, 247, 0.42), transparent 24%),
                radial-gradient(circle at 55% 120%, rgba(126, 34, 206, 0.55), transparent 42%),
                linear-gradient(135deg, #160b38 0%, #1d0f49 48%, #090716 100%) !important;
            border: 1px solid rgba(168, 85, 247, 0.42) !important;
        }

        .marketing-ai-shell section:first-of-type::after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            right: -110px;
            top: -170px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(217, 70, 239, 0.34), rgba(124, 58, 237, 0.04) 68%, transparent 70%);
            pointer-events: none;
        }

        .marketing-ai-shell h1,
        .marketing-ai-shell h2,
        .marketing-ai-shell h3,
        .marketing-ai-shell strong {
            color: #ffffff !important;
        }

        .marketing-ai-shell h1 {
            font-size: clamp(2.3rem, 4vw, 4.8rem) !important;
            line-height: 0.98 !important;
            letter-spacing: -0.055em !important;
            font-weight: 850 !important;
        }

        .marketing-ai-shell h1 .brand-gradient,
        .brand-gradient {
            background: linear-gradient(90deg, #ffffff 0%, #b794f6 46%, #d946ef 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent !important;
        }

        .marketing-ai-shell p,
        .marketing-ai-shell .text-gray-500,
        .marketing-ai-shell .text-gray-400,
        .marketing-ai-shell .dark\:text-gray-400 {
            color: var(--vitrine-muted) !important;
        }

        .marketing-ai-shell .bg-white,
        .marketing-ai-shell .dark\:bg-gray-900,
        .marketing-ai-shell .bg-gray-50,
        .marketing-ai-shell .dark\:bg-gray-950\/50,
        .marketing-ai-shell .bg-gray-900\/80 {
            background: rgba(20, 15, 46, 0.82) !important;
        }

        .marketing-ai-shell textarea,
        .marketing-ai-shell input,
        .marketing-ai-shell select {
            background: #0f0b23 !important;
            color: white !important;
            border-color: rgba(139, 92, 246, 0.34) !important;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03) !important;
        }

        .marketing-ai-shell textarea:focus,
        .marketing-ai-shell input:focus,
        .marketing-ai-shell select:focus {
            border-color: #8b5cf6 !important;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.18) !important;
        }

        .marketing-ai-shell button[type="submit"],
        .marketing-ai-shell .vitrine-primary-action {
            background: linear-gradient(135deg, #6d28d9 0%, #8b5cf6 52%, #a855f7 100%) !important;
            border: 1px solid rgba(196, 181, 253, 0.28) !important;
            box-shadow: 0 12px 30px rgba(124, 58, 237, 0.34) !important;
        }

        .marketing-ai-shell .vitrine-brand-lockup {
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.55rem 0.8rem;
            border: 1px solid rgba(196, 181, 253, 0.22);
            border-radius: 999px;
            background: rgba(20, 11, 49, 0.48);
            backdrop-filter: blur(12px);
        }

        .marketing-ai-shell .vitrine-mark {
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 12px;
            background: linear-gradient(145deg, #d946ef 0%, #8b5cf6 42%, #5b21b6 100%);
            box-shadow: 0 8px 24px rgba(139, 92, 246, 0.45);
            position: relative;
            transform: rotate(45deg);
        }

        .marketing-ai-shell .vitrine-mark::after {
            content: "";
            position: absolute;
            inset: 7px;
            border-radius: 7px;
            background: #140b31;
        }

        .marketing-ai-shell .vitrine-brand-name {
            font-weight: 850;
            letter-spacing: -0.04em;
            font-size: 1.05rem;
            color: white;
        }

        .marketing-ai-shell .vitrine-brand-name span {
            color: #b794f6;
        }

        .marketing-ai-shell .vitrine-eyebrow {
            color: #c4b5fd !important;
            letter-spacing: 0.22em;
        }

        @media (max-width: 768px) {
            .marketing-ai-shell h1 {
                font-size: 2.5rem !important;
            }
        }
    </style>
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

    <div class="marketing-ai-shell space-y-6">
        <section class="overflow-hidden rounded-3xl border p-7 text-white shadow-sm lg:p-9">
            <div class="flex flex-col gap-7 xl:flex-row xl:items-end xl:justify-between">
                <div class="max-w-3xl">
                    <div class="vitrine-brand-lockup">
                        <span class="vitrine-mark" aria-hidden="true"></span>
                        <span class="vitrine-brand-name">VITRINE IA <span>PRO</span></span>
                    </div>
                    <div class="vitrine-eyebrow mt-6 text-xs font-semibold uppercase">Marketing IA</div>
                    <h1 class="mt-3 font-bold">Marketing <span class="brand-gradient">IA</span></h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-gray-300">
                        Estratégia. Criatividade. Resultados. Um centro de marketing inteligente para planejar campanhas, coordenar agentes, produzir conteúdo e acompanhar cada etapa com controle humano.
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

        <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wider text-primary-600">Diretor de Marketing IA</div>
                    <h2 class="mt-1 text-xl font-bold text-gray-950 dark:text-white">Sessão de criação</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ $copilotSessionId }}</p>
                </div>
                <button type="button" wire:click="newCopilotSession" class="rounded-lg border px-3 py-2 text-sm">Nova sessão</button>
            </div>

            <div class="mt-5 max-h-[420px] min-h-[240px] space-y-3 overflow-y-auto rounded-xl bg-gray-50 p-4 dark:bg-gray-950/50">
                @forelse ($copilotMessages as $message)
                    <div class="flex {{ ($message['role'] ?? '') === 'user' ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] rounded-xl px-4 py-3 text-sm {{ ($message['role'] ?? '') === 'user' ? 'bg-primary-600 text-white' : 'bg-white text-gray-900 dark:bg-gray-800 dark:text-white' }}">
                            <div class="whitespace-pre-wrap">{{ $message['content'] ?? '' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="py-16 text-center text-sm text-gray-500">Converse com o Marketing IA para criar campanhas, conteúdos, Reels e anúncios.</div>
                @endforelse
            </div>

            <form wire:submit="sendCopilotMessage" class="mt-4">
                @if ($copilotError)
                    <div class="mb-3 text-sm text-danger-600">{{ $copilotError }}</div>
                @endif
                <div class="flex gap-3">
                    <textarea wire:model="copilotMessage" rows="3" maxlength="4000" class="flex-1 rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-950" placeholder="Digite o que deseja criar ou continuar..."></textarea>
                    <button type="submit" class="self-end rounded-xl bg-primary-600 px-5 py-3 text-sm font-semibold text-white">Enviar</button>
                </div>
            </form>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <div class="rounded-xl border p-4 text-sm"><strong>Metricool:</strong> publicação orgânica somente após aprovação humana.</div>
                <div class="rounded-xl border p-4 text-sm"><strong>Windsor.ai FB Ads:</strong> mídia paga somente após autorização explícita de orçamento e ativação.</div>
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
