<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-filament::section>
            <x-slot name="heading">Factory Core</x-slot>
            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <p>Módulo operacional do projeto <strong>{{ config('factory.project_name', 'Vitrine AI Pro Master') }}</strong>.</p>
                <p>Versão: <strong>{{ config('factory.version', '1.0.0') }}</strong></p>
                <p>Status: <strong>{{ config('factory.enabled', true) ? 'Ativo' : 'Inativo' }}</strong></p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Projetos</x-slot>
            <div class="text-3xl font-bold">{{ $stats['projects'] ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $stats['active_projects'] ?? 0 }} ativos</div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Execuções</x-slot>
            <div class="text-3xl font-bold">{{ $stats['executions'] ?? 0 }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $stats['running_executions'] ?? 0 }} em execução · {{ $stats['failed_executions'] ?? 0 }} com falha
            </div>
        </x-filament::section>
    </div>

    <div class="mt-6">
        <x-filament::section>
            <x-slot name="heading">Execuções por status</x-slot>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                @forelse ($executionsByStatus as $status => $total)
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ ucfirst((string) $status) }}</div>
                        <div class="text-2xl font-semibold">{{ $total }}</div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Nenhuma execução registrada.</div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
