<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">Portal do Cliente</div>
            <h1 class="mt-3 text-3xl font-bold">Área do Cliente</h1>
            <p class="mt-2 text-gray-300">Base inicial para licenças, planos, domínios, chamados, atualizações e entregas.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-4">
            @foreach (['Licenças','Domínios','Atualizações','Suporte'] as $item)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="font-semibold text-gray-950 dark:text-white">{{ $item }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Módulo previsto para o portal do cliente.</p>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
