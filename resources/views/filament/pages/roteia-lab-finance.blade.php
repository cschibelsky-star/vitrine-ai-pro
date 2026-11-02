<x-filament-panels::page>
    @php($finance = $this->finance())
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
            <h2 class="text-xl font-semibold">Roteia AI Lab — Financeiro</h2>
            <p class="text-sm text-gray-500">Consulta somente leitura. O laboratorio e independente do Core.</p>
            @unless($finance['connected'])
                <p class="mt-4 rounded-lg border border-amber-400 p-3 text-sm" role="status">
                    Dados indisponiveis ({{ $finance['reason'] }}). Nenhum saldo ou consumo foi presumido.
                </p>
            @else
                <p class="mt-4 text-sm text-green-600" role="status">Fonte do laboratorio conectada. Os valores locais sao testes e nao representam debitos confirmados pela Roteia.</p>
            @endunless
        </div>
        @if($finance['connected'])
            @php($summary = $finance['summary'])
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                @foreach(['saldo_disponivel' => 'Saldo Roteia (nao conciliado)', 'consumo_registrado_local' => 'Consumo registrado no laboratorio', 'reservas_pendentes' => 'Reservas reais (nao conciliadas)'] as $key => $label)
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                        <div class="text-sm text-gray-500">{{ $label }}</div>
                        <div class="mt-2 text-lg font-semibold">{{ data_get($summary, $key) === null ? 'Nao informado' : 'R$ ' . number_format((float) data_get($summary, $key), 2, ',', '.') }}</div>
                    </div>
                @endforeach
            </div>
            <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-700">Inicio financeiro: {{ data_get($summary, 'baseline_status', 'not_prepared') }}. Consumo real desde ativacao: {{ data_get($summary, 'consumo_real_desde_ativacao') === null ? 'Nao conciliado' : 'R$ ' . number_format((float) data_get($summary, 'consumo_real_desde_ativacao'), 2, ',', '.') }}. Reservas de teste: {{ data_get($summary, 'reservas_teste') === null ? 'Nao informado' : 'R$ ' . number_format((float) data_get($summary, 'reservas_teste'), 2, ',', '.') }}. Nenhuma zeragem do saldo Roteia foi realizada.</div>
            <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
                <h3 class="text-lg font-semibold">Projetos (registros de teste)</h3>
                <div class="mt-3 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th class="p-2">Projeto</th><th class="p-2">Status</th><th class="p-2">Consumo</th></tr></thead><tbody>
                    @forelse($finance['projects'] as $project)
                        <tr><td class="p-2">{{ data_get($project, 'name', data_get($project, 'id', '—')) }}</td><td class="p-2">{{ data_get($project, 'status', '—') }}</td><td class="p-2">{{ data_get($project, 'spent_brl') === null ? 'Nao conciliado' : 'R$ ' . number_format((float) data_get($project, 'spent_brl'), 2, ',', '.') }}</td></tr>
                    @empty <tr><td class="p-2" colspan="3">Sem projetos informados pela API.</td></tr> @endforelse
                </tbody></table></div>
            </section>
            <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
                <h3 class="text-lg font-semibold">Ultimas transacoes (historico de teste)</h3>
                <div class="mt-3 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr><th class="p-2">Request ID</th><th class="p-2">Projeto</th><th class="p-2">Estado</th><th class="p-2">Custo conciliado</th></tr></thead><tbody>
                    @forelse($finance['transactions'] as $transaction)
                        <tr><td class="p-2">{{ data_get($transaction, 'request_id', '—') }}</td><td class="p-2">{{ data_get($transaction, 'project_id', '—') }}</td><td class="p-2">{{ data_get($transaction, 'status', '—') }}</td><td class="p-2">{{ data_get($transaction, 'reconciled_brl') === null ? 'Nao conciliado' : 'R$ ' . number_format((float) data_get($transaction, 'reconciled_brl'), 2, ',', '.') }}</td></tr>
                    @empty <tr><td class="p-2" colspan="4">Sem transacoes informadas pela API.</td></tr> @endforelse
                </tbody></table></div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
