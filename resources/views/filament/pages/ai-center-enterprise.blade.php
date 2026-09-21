<x-filament-panels::page>
    <div class="space-y-8">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">IA Center</div>
            <h1 class="mt-3 text-3xl font-bold">Agentes + Orquestrador de Mídia</h1>
            <p class="mt-2 max-w-4xl text-gray-300">
                Centro técnico para acompanhar os agentes e a seleção dinâmica de modelos por disponibilidade,
                qualidade, adequação, custo, velocidade e confiabilidade.
            </p>
        </div>

        <section class="space-y-4">
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-widest text-primary-600">Orquestrador</div>
                    <h2 class="text-2xl font-bold text-gray-950 dark:text-white">Matriz operacional em tempo quase real</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Catálogo atualizado a cada 60 segundos. Modelos indisponíveis recebem score zero e saem da disputa.
                    </p>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Atualizado: {{ IlluminateSupportCarbon::parse($orchestratorUpdatedAt)->format('d/m/Y H:i:s') }}
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                @foreach ($orchestratorProfiles as $profile => $weights)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-xs font-semibold uppercase tracking-wider text-primary-600">{{ $profile }}</div>
                        <div class="mt-3 space-y-1 text-xs text-gray-600 dark:text-gray-300">
                            <div>Qualidade: {{ number_format(($weights['quality'] ?? 0) * 100, 0) }}%</div>
                            <div>Adequação: {{ number_format(($weights['suitability'] ?? 0) * 100, 0) }}%</div>
                            <div>Custo: {{ number_format(($weights['cost'] ?? 0) * 100, 0) }}%</div>
                            <div>Velocidade: {{ number_format(($weights['speed'] ?? 0) * 100, 0) }}%</div>
                            <div>Confiabilidade: {{ number_format(($weights['reliability'] ?? 0) * 100, 0) }}%</div>
                        </div>
                    </div>
                @endforeach
            </div>

            @foreach (['image' => 'Imagem', 'video' => 'Vídeo'] as $capability => $label)
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                        <div>
                            <h3 class="font-semibold text-gray-950 dark:text-white">{{ $label }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Ranking balanceado entre modelos compatíveis encontrados no catálogo.
                            </p>
                        </div>
                        @php
                            $availableCount = collect($orchestratorModels[$capability] ?? [])->where('status', 'available')->count();
                        @endphp
                        <div class="rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-700 dark:bg-success-950/40 dark:text-success-400">
                            {{ $availableCount }} disponíveis
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
                            <thead class="bg-gray-50 dark:bg-gray-950/40">
                                <tr class="text-left text-xs uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3">Prioridade</th>
                                    <th class="px-4 py-3">Modelo</th>
                                    <th class="px-4 py-3">Provider</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Score</th>
                                    <th class="px-4 py-3">Custo estimado</th>
                                    <th class="px-4 py-3">Motivo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse (($orchestratorModels[$capability] ?? []) as $row)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-gray-950 dark:text-white">
                                            {{ $row['priority'] ? '#'.$row['priority'] : '—' }}
                                        </td>
                                        <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-gray-100">{{ $row['model'] }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $row['provider'] }}</td>
                                        <td class="px-4 py-3">
                                            @if ($row['status'] === 'available')
                                                <span class="rounded-full bg-success-50 px-2 py-1 text-xs font-medium text-success-700 dark:bg-success-950/40 dark:text-success-400">disponível</span>
                                            @else
                                                <span class="rounded-full bg-danger-50 px-2 py-1 text-xs font-medium text-danger-700 dark:bg-danger-950/40 dark:text-danger-400">{{ $row['status'] }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-gray-950 dark:text-white">{{ number_format($row['score'], 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                            {{ $row['price_brl'] !== null ? 'R$ '.number_format($row['price_brl'], 4, ',', '.') : 'não informado' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $row['reason'] ?: 'compatível com a capability' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                                            Nenhum modelo compatível foi retornado pelo catálogo agora.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </section>

        <section class="space-y-4">
            <div>
                <div class="text-xs font-semibold uppercase tracking-widest text-primary-600">Agentes</div>
                <h2 class="text-2xl font-bold text-gray-950 dark:text-white">Agentes especializados</h2>
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                @foreach (['IA Comercial','IA Marketing','IA Desenvolvedora','IA QA','IA Licitações','IA Turismo','IA Saúde','IA Atendimento','IA Deploy'] as $agent)
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-xs uppercase tracking-widest text-success-600">online</div>
                        <h3 class="mt-2 font-semibold text-gray-950 dark:text-white">{{ $agent }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Agente especializado do ecossistema.</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>
