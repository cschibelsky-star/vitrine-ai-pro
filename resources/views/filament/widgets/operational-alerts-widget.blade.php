<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-3">
            <div>
                <h2 class="text-lg font-bold tracking-tight">Centro Operacional Master</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Gestão executiva da Vitrine AI Pro: clientes, leads, contratos, cobranças, licenças e módulos.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-800 dark:bg-warning-950">
                    <div class="text-sm font-medium text-warning-700 dark:text-warning-300">Licenças vencendo</div>
                    <div class="mt-1 text-2xl font-bold text-warning-900 dark:text-warning-100">{{ $licensesExpiring }}</div>
                    <div class="mt-1 text-xs text-warning-700 dark:text-warning-300">Próximos 15 dias</div>
                </div>

                <div class="rounded-xl border border-info-200 bg-info-50 p-4 dark:border-info-800 dark:bg-info-950">
                    <div class="text-sm font-medium text-info-700 dark:text-info-300">Cobranças abertas</div>
                    <div class="mt-1 text-2xl font-bold text-info-900 dark:text-info-100">{{ $paymentsOpen }}</div>
                    <div class="mt-1 text-xs text-info-700 dark:text-info-300">Aguardando pagamento</div>
                </div>

                <div class="rounded-xl border border-danger-200 bg-danger-50 p-4 dark:border-danger-800 dark:bg-danger-950">
                    <div class="text-sm font-medium text-danger-700 dark:text-danger-300">Cobranças vencidas</div>
                    <div class="mt-1 text-2xl font-bold text-danger-900 dark:text-danger-100">{{ $paymentsOverdue }}</div>
                    <div class="mt-1 text-xs text-danger-700 dark:text-danger-300">Exigem acompanhamento</div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
