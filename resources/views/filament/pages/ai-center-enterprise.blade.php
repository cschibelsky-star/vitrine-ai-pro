<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">IA Center</div>
            <h1 class="mt-3 text-3xl font-bold">Agentes de IA</h1>
            <p class="mt-2 text-gray-300">Central dos agentes especializados da Vitrine AI Pro.</p>
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
    </div>
</x-filament-panels::page>
