<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">IA Center</div>
            <h1 class="mt-3 text-3xl font-bold">Capacidades de IA</h1>
            <p class="mt-2 text-gray-300">Central operacional das capacidades de IA da Vitrine AI Pro.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            <a href="{{ \App\Filament\Pages\MarketingDashboard::getUrl() }}" class="group rounded-xl border border-primary-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-400 hover:shadow-md dark:border-primary-900 dark:bg-gray-900">
                <div class="text-xs font-semibold uppercase tracking-widest text-primary-600">Core Capability</div>
                <h3 class="mt-2 text-lg font-semibold text-gray-950 dark:text-white">Marketing IA</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Equipe de 9 agentes, pipeline de campanha, aprovações, consumo e auditoria.</p>
                <div class="mt-4 text-sm font-semibold text-primary-600">Abrir dashboard →</div>
            </a>

            @foreach ([
                ['IA Comercial', 'Capacidade comercial do ecossistema.'],
                ['IA Desenvolvedora', 'Capacidade de engenharia e desenvolvimento.'],
                ['IA QA', 'Validação e garantia de qualidade.'],
                ['IA Licitações', 'Apoio especializado em compras públicas.'],
                ['IA Turismo', 'Capacidade especializada para turismo e destinos.'],
                ['IA Saúde', 'Capacidade especializada para fluxos de saúde.'],
                ['IA Atendimento', 'Assistência e atendimento operacional.'],
                ['IA Deploy', 'Capacidade de implantação controlada.'],
            ] as [$agent, $description])
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs uppercase tracking-widest text-gray-400">capacidade registrada</div>
                    <h3 class="mt-2 font-semibold text-gray-950 dark:text-white">{{ $agent }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
