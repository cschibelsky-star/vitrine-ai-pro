<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">IA Center</div>
            <h1 class="mt-3 text-3xl font-bold">Capacidades de IA</h1>
            <p class="mt-2 text-gray-300">Central das capacidades especializadas da Vitrine AI Pro.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <a href="{{ \App\Filament\Pages\MarketingDashboard::getUrl() }}" class="block rounded-xl border border-primary-200 bg-white p-6 shadow-sm transition hover:border-primary-400 hover:shadow-md dark:border-primary-900/60 dark:bg-gray-900">
                <div class="text-xs uppercase tracking-widest text-primary-600">capacidade operacional</div>
                <h3 class="mt-2 font-semibold text-gray-950 dark:text-white">Marketing IA</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Equipe de agentes, pipeline, Campaign State, artefatos e QA.</p>
            </a>

            @foreach (['IA Comercial','IA Desenvolvedora','IA QA','IA Licitações','IA Turismo','IA Saúde','IA Atendimento','IA Deploy'] as $agent)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs uppercase tracking-widest text-gray-500">capacidade registrada</div>
                    <h3 class="mt-2 font-semibold text-gray-950 dark:text-white">{{ $agent }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Integração operacional ainda não apresentada neste painel.</p>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
