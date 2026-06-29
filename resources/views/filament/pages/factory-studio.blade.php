<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                Factory Studio
            </h2>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Produza sistemas a partir de uma solicitação livre. Esta primeira versão usa o fluxo homologado:
                pedido livre → produto resolvido → produção → dry-run de instalação.
            </p>

            <div class="mt-4 rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-950">
                <div class="font-semibold text-gray-800 dark:text-gray-100">Solicitação padrão:</div>
                <div class="mt-1 text-gray-600 dark:text-gray-400">{{ $this->requestText }}</div>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Produto piloto</div>
                <div class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">GOV360</div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Consultor AI GOV360</div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Último status</div>
                <div class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">
                    {{ $this->lastStatus ?? 'aguardando' }}
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Produção / instalação</div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Relatório</div>
                <div class="mt-2 break-all text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $this->lastReportPath ?? 'Nenhum relatório carregado' }}
                </div>
            </div>
        </div>

        @php
            $report = $this->getProductionReport();
            $modules = $this->getBuildModules();
        @endphp

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Módulos produzidos</h3>

            @if (count($modules))
                <div class="mt-4 grid gap-3 md:grid-cols-5">
                    @foreach ($modules as $module)
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm font-medium text-gray-800 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-100">
                            {{ $module }}
                        </div>
                    @endforeach
                </div>
            @else
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Nenhum módulo produzido ainda. Clique em Produzir.
                </p>
            @endif
        </div>

        @if ($report)
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Resumo da última produção</h3>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Produto</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $report['product_name'] ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Status</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $report['status'] ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Modo</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $report['mode'] ?? '-' }}</div>
                    </div>

                    <div>
                        <div class="text-xs uppercase text-gray-500 dark:text-gray-400">Produzido em</div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $report['produced_at'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
        @endif

        @if ($this->lastOutput)
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Retorno do comando</h3>
                <pre class="mt-4 max-h-96 overflow-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $this->lastOutput }}</pre>
            </div>
        @endif
    </div>
</x-filament-panels::page>
