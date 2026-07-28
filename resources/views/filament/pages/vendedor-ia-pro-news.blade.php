<x-filament-panels::page>
    <div class="space-y-6">

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-sm font-semibold text-primary-600">Integração Comercial Master</p>
                    <h2 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        VendedorIA Pro News
                    </h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Módulo integrado ao Comercial Master para captar interessados em portais de notícias automatizados,
                        registrar a origem do lead, sugerir plano comercial e acompanhar os contatos no funil.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="{{ $this->getLandingUrl() }}"
                        target="_blank"
                        class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500"
                    >
                        Abrir landing
                    </a>

                    <a
                        href="{{ url('/admin/leads') }}"
                        class="inline-flex items-center rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800"
                    >
                        Ver todos os leads
                    </a>
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Leads captados</p>
                <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">{{ $this->getTotalLeads() }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Leads hoje</p>
                <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">{{ $this->getLeadsHoje() }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Valor estimado</p>
                <p class="mt-2 text-3xl font-bold text-gray-950 dark:text-white">
                    R$ {{ number_format($this->getValorEstimado(), 2, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">Links operacionais</h3>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">Landing de campanha</label>
                        <input
                            readonly
                            value="{{ $this->getLandingUrl() }}"
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-200">Código do widget</label>
                        <textarea
                            readonly
                            rows="3"
                            class="mt-1 w-full rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >{{ $this->getWidgetCode() }}</textarea>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-bold text-gray-950 dark:text-white">Status da integração</h3>

                <div class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-800">
                        <span class="text-gray-600 dark:text-gray-300">Endpoint público</span>
                        <span class="font-semibold text-green-600">Ativo</span>
                    </div>

                    <div class="flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-800">
                        <span class="text-gray-600 dark:text-gray-300">Gravação no Comercial Master</span>
                        <span class="font-semibold text-green-600">Ativa</span>
                    </div>

                    <div class="flex items-center justify-between border-b border-gray-100 pb-2 dark:border-gray-800">
                        <span class="text-gray-600 dark:text-gray-300">Origem padrão</span>
                        <span class="font-semibold text-gray-900 dark:text-white">VendedorIA Pro News</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-300">Produto de interesse</span>
                        <span class="font-semibold text-gray-900 dark:text-white">VitrineIA Pro News</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">Últimos leads captados</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Exibe somente contatos com origem VendedorIA Pro News.
                    </p>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                            <th class="py-3 pr-4 font-semibold">ID</th>
                            <th class="py-3 pr-4 font-semibold">Empresa</th>
                            <th class="py-3 pr-4 font-semibold">Contato</th>
                            <th class="py-3 pr-4 font-semibold">Plano</th>
                            <th class="py-3 pr-4 font-semibold">Valor</th>
                            <th class="py-3 pr-4 font-semibold">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->getUltimosLeads() as $lead)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-200">{{ $lead->id }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-200">{{ $lead->empresa ?? '-' }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-200">{{ $lead->contato ?? '-' }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-200">{{ $lead->plano_sugerido ?? '-' }}</td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-200">
                                    R$ {{ number_format((float) ($lead->valor_estimado ?? 0), 2, ',', '.') }}
                                </td>
                                <td class="py-3 pr-4 text-gray-700 dark:text-gray-200">
                                    {{ optional($lead->created_at)->format('d/m/Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500 dark:text-gray-400">
                                    Nenhum lead do VendedorIA Pro News encontrado ainda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
