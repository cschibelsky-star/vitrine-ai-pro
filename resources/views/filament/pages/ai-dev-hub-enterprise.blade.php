<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">Uso interno</div>
            <h1 class="mt-3 text-3xl font-bold">Hub IA Dev</h1>
            <p class="mt-2 max-w-3xl text-gray-300">Conector interno para consultar outras inteligências via Vitrine IA Hub durante desenvolvimento, revisão e diagnóstico técnico.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500">Status</div>
                <div class="mt-2 text-xl font-bold {{ $enabled ? 'text-success-600' : 'text-danger-600' }}">{{ $enabled ? 'Habilitado' : 'Desabilitado' }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500">Provider padrão</div>
                <div class="mt-2 text-xl font-bold text-gray-950 dark:text-white">{{ $defaultProvider }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500">Custo no mês</div>
                <div class="mt-2 text-xl font-bold text-gray-950 dark:text-white">R$ {{ number_format($cost, 4, ',', '.') }}</div>
                <div class="mt-1 text-xs text-gray-400">limite R$ {{ number_format($monthlyLimit, 2, ',', '.') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-sm text-gray-500">Uso no mês</div>
                <div class="mt-2 text-xl font-bold text-gray-950 dark:text-white">{{ number_format($requests, 0, ',', '.') }} chamadas</div>
                <div class="mt-1 text-xs text-gray-400">{{ number_format($tokens, 0, ',', '.') }} tokens · {{ number_format($credits, 2, ',', '.') }} créditos</div>
            </div>
        </div>

        @if (! $ready)
            <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm text-warning-900 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-100">
                O schema do Vitrine IA Hub ainda não foi aplicado. Execute as migrations antes da homologação do Hub IA Dev.
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs uppercase tracking-widest text-primary-600">Ferramentas previstas</div>
                <div class="mt-4 space-y-3 text-sm">
                    <div><strong>ai_dev_chat</strong> — consulta a um modelo.</div>
                    <div><strong>ai_dev_compare</strong> — comparação entre até {{ $maxCompareModels }} modelos.</div>
                    <div><strong>ai_dev_code_review</strong> — revisão técnica de código/diff.</div>
                    <div><strong>ai_dev_models</strong> — catálogo e custos.</div>
                    <div><strong>ai_dev_usage</strong> — consumo interno.</div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs uppercase tracking-widest text-primary-600">Separação de consumo</div>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Todas as chamadas do conector são registradas como <code>internal_development.*</code>. Esse consumo fica separado de clientes, licenças e franquias comerciais.</p>
            </div>
        </div>
    </div>
</x-filament-panels::page>
