<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">Projetos</div>
            <h1 class="mt-3 text-3xl font-bold">Aplicações Geradas</h1>
            <p class="mt-2 text-gray-300">Catálogo dos sistemas produzidos pela Factory.</p>
        </div>
        @php($projects = $this->getProjects())
        @if (count($projects))
            <div class="grid gap-6 md:grid-cols-3">
                @foreach ($projects as $project)
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-xs uppercase tracking-widest text-primary-600">{{ $project['status'] }}</div>
                        <h3 class="mt-2 text-lg font-bold text-gray-950 dark:text-white">{{ $project['name'] }}</h3>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $project['description'] }}</p>
                        <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">Módulos: {{ $project['modules'] }}</div>
                        <div class="mt-2 text-xs text-gray-400">{{ $project['slug'] }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-500 shadow-sm dark:border-gray-800 dark:bg-gray-900">Nenhum projeto encontrado ainda.</div>
        @endif
    </div>
</x-filament-panels::page>
