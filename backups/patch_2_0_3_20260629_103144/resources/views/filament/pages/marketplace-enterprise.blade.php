<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">Marketplace</div>
            <h1 class="mt-3 text-3xl font-bold">Produtos Homologados</h1>
            <p class="mt-2 text-gray-300">Produtos publicados, templates, componentes e soluções licenciáveis da Vitrine AI Pro.</p>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach (['Consultor AI GOV360', 'Guia Digital da Cidade', 'TV Digital Enterprise'] as $product)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <div class="text-xs uppercase tracking-widest text-gray-500">Produto estratégico</div>
                    <h3 class="mt-2 text-lg font-bold text-gray-950 dark:text-white">{{ $product }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Aguardando homologação para publicação.</p>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
