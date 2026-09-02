<x-filament-panels::page>
    <div class="via-unified-shell space-y-6">
        <div class="rounded-2xl bg-gray-950 p-6 text-white">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="text-sm uppercase tracking-widest text-primary-300">VIA Agent Hub</div>
                    <h1 class="mt-2 text-2xl font-bold">Conversa operacional · Factory</h1>
                    <p class="mt-2 max-w-3xl text-sm text-gray-300">Structured Mission Runtime v1 com Mission Record, Evidence Pack, Grounding Strict, contratos e handoffs auditáveis.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-success-500/20 px-3 py-1 font-medium text-success-200">OBSERVER</span>
                    <span class="rounded-full bg-primary-500/20 px-3 py-1 font-medium text-primary-200">Factory</span>
                    <span class="rounded-full bg-warning-500/20 px-3 py-1 font-medium text-warning-200">Execução exige owner</span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 2xl:grid-cols-[260px_minmax(0,1fr)_360px]">
            <aside class="space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="border-b border-gray-200 p-4 dark:border-gray-800">
                        <button type="button" wire:click="newConversation" wire:loading.attr="disabled" wire:target="sendMessage" class="w-full rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50">Nova conversa</button>
                    </div>
                    <div class="max-h-[68vh] space-y-2 overflow-y-auto p-3">
                        @foreach ($sessions as $session)
                            <button type="button" wire:click="loadConversation({{ $session['id'] }})" class="w-full rounded-xl border p-3 text-left transition {{ $session['active'] ? 'border-primary-400 bg-primary-50 dark:border-primary-700 dark:bg-primary-950' : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:hover:bg-gray-800' }}">
                                <div class="line-clamp-2 text-sm font-medium text-gray-900 dark:text-white">{{ $session['title'] }}</div>
                                <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-gray-400"><span>{{ $session['target_project_id'] }}</span><span>{{ $session['last_activity'] }}</span></div>
                            </button>
                        @endforeach
                    </div>
                </div>
            </aside>

            <section class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="border-b border-gray-200 p-5 dark:border-gray-800">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-gray-500">Domínio</label>
                            <select wire:model="domain" class="block w-full rounded-xl border-gray-300 bg-white text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"><option value="factory">Factory</option></select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wider text-gray-500">Projeto-alvo</label>
                            <select wire:model="targetProjectId" class="block w-full rounded-xl border-gray-300 bg-white text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"><option value="factory">Factory</option></select>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Outros projetos só serão liberados quando tiverem collector e evidências próprias.</p>
                </div>

                <div class="max-h-[60vh] min-h-[430px] space-y-4 overflow-y-auto p-5" wire:loading.class="opacity-70" wire:target="sendMessage">
                    @foreach ($messages as $item)
                        @php($isUser = ($item['role'] ?? '') === 'user')
                        <div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[92%] rounded-2xl px-4 py-3 {{ $isUser ? 'bg-primary-600 text-white' : 'border border-gray-200 bg-gray-50 text-gray-800 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-100' }}">
                                <div class="mb-2 flex items-center justify-between gap-4 text-[11px] font-semibold uppercase tracking-wider {{ $isUser ? 'text-primary-100' : 'text-gray-400' }}"><span>{{ $isUser ? 'Você' : 'VIA' }}</span><span>{{ $item['created_at'] ?? '' }}</span></div>
                                <div class="whitespace-pre-wrap text-sm leading-6">{{ $item['content'] ?? '' }}</div>
                                @if (! $isUser && ! empty($item['meta']) && ($item['meta']['status'] ?? null) !== 'error')
                                    <div class="mt-4 grid gap-2 border-t border-gray-200 pt-3 text-[11px] text-gray-500 dark:border-gray-800 dark:text-gray-400 md:grid-cols-2">
                                        @if (! empty($item['meta']['mission_id']))<div><span class="font-medium">Mission ID:</span> <span class="break-all font-mono">{{ $item['meta']['mission_id'] }}</span></div>@endif
                                        @if (! empty($item['meta']['finish_reason']))<div><span class="font-medium">Finish:</span> {{ $item['meta']['finish_reason'] }}</div>@endif
                                        @if (isset($item['meta']['tokens']))<div><span class="font-medium">Tokens:</span> {{ number_format((int) $item['meta']['tokens'], 0, ',', '.') }}</div>@endif
                                        @if (isset($item['meta']['cost_brl']))<div><span class="font-medium">Custo:</span> R$ {{ number_format((float) $item['meta']['cost_brl'], 6, ',', '.') }}</div>@endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    <div wire:loading wire:target="sendMessage" class="rounded-xl border border-primary-200 bg-primary-50 p-3 text-sm text-primary-800 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-200">VIA executando uma missão estruturada com seis papéis e uma única chamada de IA…</div>
                </div>

                <form wire:submit="sendMessage" class="border-t border-gray-200 p-5 dark:border-gray-800">
                    <label for="via-message" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200">Sua mensagem</label>
                    <textarea id="via-message" wire:model="message" rows="4" maxlength="4000" placeholder="Ex.: Analise o estado atual da Factory e indique as três próximas prioridades técnicas." class="block w-full rounded-xl border-gray-300 bg-white text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white"></textarea>
                    <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs text-gray-500 dark:text-gray-400">Cada envio gera uma missão auditável. Nenhuma mensagem autoriza execução automática.</div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage" class="inline-flex items-center justify-center rounded-xl bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 disabled:opacity-60"><span wire:loading.remove wire:target="sendMessage">Enviar para VIA</span><span wire:loading wire:target="sendMessage">Analisando…</span></button>
                    </div>
                </form>
            </section>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-3">
                        <div class="text-xs uppercase tracking-widest text-primary-600">Pipeline real</div>
                        @if ($lastMission)
                            <span class="rounded-full px-2 py-1 text-[10px] font-semibold {{ ($lastMission['structured_runtime_valid'] ?? false) ? 'bg-success-100 text-success-700 dark:bg-success-950 dark:text-success-200' : 'bg-warning-100 text-warning-700 dark:bg-warning-950 dark:text-warning-200' }}">{{ ($lastMission['structured_runtime_valid'] ?? false) ? 'VALIDADO' : 'FAIL-CLOSED' }}</span>
                        @endif
                    </div>
                    <div class="mt-4 space-y-2">
                        @php($labels = ['orchestrator'=>'Orchestrator','project_manager'=>'Project Manager','architect'=>'Architect','qa'=>'QA','auditor'=>'Auditor','report'=>'Report'])
                        @foreach ($labels as $role => $agent)
                            @php($state = $lastMission['agent_states'][$role] ?? null)
                            @php($decision = $state['decision_state'] ?? 'aguardando')
                            <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-800">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $agent }}</div>
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $decision }}</span>
                                </div>
                                @if ($state)
                                    <div class="mt-2 text-[11px] text-gray-400">Contrato: {{ ($state['valid'] ?? false) ? 'válido' : 'inválido' }} · Handoff: {{ ($state['handoff_allowed'] ?? false) ? 'liberado' : 'não liberado' }}</div>
                                    @if (! empty($state['evidence_refs']))<div class="mt-1 text-[10px] text-gray-400">Evidências: {{ implode(', ', array_slice($state['evidence_refs'], 0, 3)) }}</div>@endif
                                    @if (! empty($state['errors']))<div class="mt-1 text-[10px] text-danger-600">{{ implode(', ', $state['errors']) }}</div>@endif
                                @else
                                    <div class="mt-2 text-[11px] text-gray-400">Aguardando missão estruturada.</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs uppercase tracking-widest text-success-600">Governança</div>
                    <div class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex justify-between gap-4"><span>Ler</span><strong>Permitido</strong></div><div class="flex justify-between gap-4"><span>Analisar</span><strong>Permitido</strong></div><div class="flex justify-between gap-4"><span>Recomendar</span><strong>Permitido</strong></div><div class="flex justify-between gap-4"><span>Escrever</span><strong>Bloqueado</strong></div><div class="flex justify-between gap-4"><span>Deploy</span><strong>Bloqueado</strong></div><div class="flex justify-between gap-4"><span>Destrutivo</span><strong>Bloqueado</strong></div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs uppercase tracking-widest text-primary-600">Última missão</div>
                    @if ($lastMission)
                        <div class="mt-3 space-y-3 text-xs text-gray-600 dark:text-gray-300">
                            <div><div class="text-gray-400">Mission ID</div><div class="mt-1 break-all font-mono">{{ $lastMission['mission_id'] ?? '—' }}</div></div>
                            <div><div class="text-gray-400">Evidence SHA-256</div><div class="mt-1 break-all font-mono">{{ $lastMission['evidence_sha256'] ?? '—' }}</div></div>
                            <div><div class="text-gray-400">Runtime</div><strong>{{ $lastMission['runtime_version'] ?? '—' }}</strong></div>
                            <div class="grid grid-cols-2 gap-3"><div><div class="text-gray-400">Grounding</div><strong>{{ $lastMission['grounding'] ?? '—' }}</strong></div><div><div class="text-gray-400">Finish</div><strong>{{ $lastMission['finish_reason'] ?? '—' }}</strong></div></div>
                            @if (! empty($lastMission['chain_errors']))<div><div class="text-gray-400">Chain errors</div><div class="mt-1 text-danger-600">{{ implode(', ', $lastMission['chain_errors']) }}</div></div>@endif
                        </div>
                    @else
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Nenhuma missão executada nesta conversa.</p>
                    @endif
                </div>

                <div class="rounded-2xl border border-warning-200 bg-warning-50 p-5 text-sm text-warning-900 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-100"><strong>Regra operacional:</strong> ações reais continuam exigindo autorização explícita do owner e não são executadas por esta tela.</div>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
