<x-filament-panels::page>
    <style>
        .vip-hero {
            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, .38), transparent 36%),
                radial-gradient(circle at bottom left, rgba(14, 165, 233, .28), transparent 34%),
                linear-gradient(135deg, #020617 0%, #0f172a 52%, #111827 100%);
        }
        .vip-card { transition: all .2s ease; }
        .vip-card:hover { transform: translateY(-2px); }
    </style>

    <div class="space-y-8">
        <section class="vip-hero relative overflow-hidden rounded-[2rem] p-8 text-white shadow-2xl ring-1 ring-white/10">
            <div class="relative z-10 grid gap-10 lg:grid-cols-[1.3fr_.7fr] lg:items-center">
                <div>
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.25em] text-sky-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        Vitrine AI Pro Enterprise 6.0 RC3
                    </div>

                    <h1 class="mt-6 max-w-4xl text-4xl font-black tracking-tight md:text-5xl">
                        Centro Operacional Inteligente
                    </h1>

                    <p class="mt-5 max-w-3xl text-base leading-7 text-slate-300">
                        Cockpit executivo para operar a Vitrine AI Pro: clientes, produtos, licenças,
                        comercial, Factory Studio, projetos, marketplace e entrega SaaS.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="/admin/factory-studio-enterprise" class="rounded-2xl bg-sky-500 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-sky-500/25 hover:bg-sky-400">Abrir Factory Studio</a>
                        <a href="/admin/generated-projects" class="rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-bold text-white hover:bg-white/15">Projetos Gerados</a>
                        <a href="/admin/marketplace-enterprise" class="rounded-2xl border border-white/15 bg-white/10 px-5 py-3 text-sm font-bold text-white hover:bg-white/15">Marketplace</a>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-white/10 bg-white/10 p-5 shadow-xl">
                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-slate-300">Pipeline Enterprise</div>
                    <div class="mt-5 space-y-3">
                        @foreach (['Pedido', 'Cliente', 'Licença', 'Factory', 'Projeto', 'Homologação', 'Publicação'] as $step)
                            <div class="flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/10">
                                <span class="text-sm font-semibold text-slate-100">{{ $step }}</span>
                                <span class="rounded-full bg-emerald-400/15 px-3 py-1 text-xs font-bold text-emerald-200">ativo</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-5 md:grid-cols-4">
            @foreach ([
                ['label' => 'Produtos Oficiais', 'value' => '4', 'desc' => 'SaaS comercial'],
                ['label' => 'Projetos Factory', 'value' => $this->countProjects(), 'desc' => 'Blueprints gerados'],
                ['label' => 'Pedidos Comerciais', 'value' => $this->countCommercialIntakes(), 'desc' => 'Comercial → Factory'],
                ['label' => 'Release', 'value' => 'RC3', 'desc' => 'Enterprise 6.0'],
            ] as $card)
                <div class="vip-card rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="text-sm font-semibold text-slate-500 dark:text-slate-400">{{ $card['label'] }}</div>
                    <div class="mt-3 text-4xl font-black tracking-tight text-slate-950 dark:text-white">{{ $card['value'] }}</div>
                    <div class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $card['desc'] }}</div>
                </div>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <div class="xl:col-span-2 rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">Produtos Estratégicos</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Catálogo comercial conectado ao fluxo de produção da Factory.</p>
                    </div>
                    <span class="rounded-full bg-sky-500/10 px-4 py-2 text-xs font-bold text-sky-600">Marketplace-ready</span>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach ($this->getProducts() as $product)
                        <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="text-xs font-bold uppercase tracking-[0.18em] text-sky-600">{{ $product['tag'] }}</div>
                                    <h3 class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ $product['name'] }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $product['desc'] }}</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-300 dark:ring-slate-700">{{ $product['status'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">IA Center</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Agentes especializados do ecossistema.</p>
                    <div class="mt-5 space-y-3">
                        @foreach (['IA Comercial', 'IA Arquiteta', 'IA Desenvolvedora', 'IA QA', 'IA Deploy'] as $agent)
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-300">{{ $agent }}</span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">Próxima Entrega</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">Conectar formulário/checkout do site comercial ao intake automático da Factory.</p>
                </div>
            </div>
        </section>
    </div>
</x-filament-panels::page>
