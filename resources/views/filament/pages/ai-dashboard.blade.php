<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-filament::section>
            <div class="text-sm text-gray-500">Agentes cadastrados</div>
            <div class="text-3xl font-bold">{{ $agentsCount }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500">Provedores IA</div>
            <div class="text-3xl font-bold">{{ $providersCount }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500">Execuções hoje</div>
            <div class="text-3xl font-bold">{{ $executionsToday }}</div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-sm text-gray-500">Alertas abertos</div>
            <div class="text-3xl font-bold">{{ $openAlerts }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Centro IA Operacional</x-slot>
        <p>Dashboard inicial ativo. Próximo passo: cadastrar provedores, agentes e validar execuções reais.</p>
    </x-filament::section>
</x-filament-panels::page>
