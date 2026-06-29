<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-2xl bg-gray-950 p-8 text-white">
            <div class="text-sm uppercase tracking-widest text-primary-300">Factory Studio 2.0</div>
            <h1 class="mt-3 text-3xl font-bold">Software Factory Enterprise</h1>
            <p class="mt-2 text-gray-300">Produção assistida por IA: solicitação, arquitetura, blueprint, build, QA e publicação.</p>
        </div>
        <div class="grid gap-6 md:grid-cols-5">
            @foreach (['IA Arquiteta','IA Desenvolvedora','IA QA','IA Documentação','IA Deploy'] as $item)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="font-semibold text-gray-950 dark:text-white">{{ $item }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Agente especializado.</p>
                </div>
            @endforeach
        </div>
        @if ($this->lastOutput)
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">Última execução</h3>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Status: {{ $this->lastStatus }}</div>
                <pre class="mt-4 max-h-96 overflow-auto rounded-lg bg-gray-950 p-4 text-xs text-gray-100">{{ $this->lastOutput }}</pre>
            </div>
        @endif
    </div>
</x-filament-panels::page>
