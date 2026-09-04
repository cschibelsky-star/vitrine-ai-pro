<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">Factory Studio 2.0</div>
            <h1 class="mt-3 text-3xl font-bold">Software Factory Enterprise</h1>
            <p class="mt-2 text-gray-300">Produção assistida por IA: solicitação, arquitetura, blueprint, build, QA e publicação.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-5">
            @foreach (['IA Arquiteta','IA Desenvolvedora','IA QA','IA Documentação','IA Deploy'] as $item)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="font-semibold text-gray-950 dark:text-white">{{ $item }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Agente especializado.</p>
                </div>
            @endforeach
        </div>

        @if ($this->lastReport)
            @php
                $report = $this->lastReport;
                $steps = $report['steps'] ?? [];
                $failedStage = $report['failed_stage'] ?? null;
            @endphp

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</div>
                    <div class="mt-2 text-lg font-semibold {{ ($report['status'] ?? null) === 'finished' ? 'text-success-600' : 'text-danger-600' }}">
                        {{ ($report['status'] ?? 'unknown') === 'finished' ? 'Concluído' : 'Falhou' }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Produção</div>
                    <div class="mt-2 break-all text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $report['production_id'] ?? '-' }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Blueprint</div>
                    <div class="mt-2 break-all text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $report['blueprint'] ?? '-' }}
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs font-medium uppercase tracking-wide text-gray-500">Duração</div>
                    <div class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">
                        {{ number_format((float) ($report['duration_seconds'] ?? 0), 3, ',', '.') }}s
                    </div>
                </div>
            </div>

            @if ($failedStage || ! empty($report['error']))
                <div class="rounded-xl border border-danger-200 bg-danger-50 p-5 text-danger-800 dark:border-danger-900 dark:bg-danger-950/30 dark:text-danger-200">
                    <div class="font-semibold">Pipeline interrompido</div>
                    <div class="mt-1 text-sm">Etapa: {{ $failedStage ?? 'não identificada' }}</div>
                    @if (! empty($report['error']))
                        <div class="mt-2 whitespace-pre-wrap text-sm">{{ $report['error'] }}</div>
                    @endif
                </div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Pipeline da última execução</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Cada etapa é registrada individualmente no relatório da produção.</p>
                    </div>
                    <div class="text-sm text-gray-500">{{ count($steps) }} etapas</div>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($steps as $name => $path)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800">
                            <div class="font-medium text-gray-950 dark:text-white">{{ str($name)->replace('_', ' ')->headline() }}</div>
                            <div class="mt-2 break-all text-xs text-gray-500 dark:text-gray-400">{{ $path }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">Nenhuma etapa registrada.</div>
                    @endforelse
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Contexto da produção</h3>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">Modo</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ $report['mode'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Blueprint path</dt>
                            <dd class="break-all font-medium text-gray-950 dark:text-white">{{ $report['blueprint_path'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Production path</dt>
                            <dd class="break-all font-medium text-gray-950 dark:text-white">{{ $report['production_path'] ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Storage compartilhado preservado</dt>
                            <dd class="font-medium text-gray-950 dark:text-white">{{ ($report['shared_storage_untouched'] ?? false) ? 'Sim' : 'Não' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Relatório</h3>
                    <p class="mt-2 break-all text-sm text-gray-500 dark:text-gray-400">{{ $report['path'] ?? 'Relatório ainda não persistido.' }}</p>
                    @if (! empty($report['final_note']))
                        <div class="mt-4 rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-950 dark:text-gray-300">
                            {{ $report['final_note'] }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if ($this->lastOutput)
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Saída técnica</h3>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Status: {{ $this->lastStatus }}</div>
                <pre class="mt-4 max-h-96 overflow-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $this->lastOutput }}</pre>
            </div>
        @endif
    </div>
</x-filament-panels::page>
