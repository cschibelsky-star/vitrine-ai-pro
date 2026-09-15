@if(auth()->check() && auth()->user()?->isAdmin())
    @php
        $viaFactoryConfig = [
            'contextUrl' => route('via.factory.context'),
            'chatUrl' => route('via.factory.chat'),
            'transcribeUrl' => route('via.factory.transcribe'),
            'actionUrl' => route('via.factory.action'),
            'csrfToken' => csrf_token(),
            'user' => [
                'id' => auth()->id(),
                'name' => auth()->user()?->name,
            ],
            'version' => '3.1.0',
            'viaOrigin' => 'https://via.hml.vitrineiapro.com.br',
            'widgetJs' => 'https://via.hml.vitrineiapro.com.br/widget/via-widget.js',
            'widgetCss' => 'https://via.hml.vitrineiapro.com.br/widget/via-widget.css',
        ];
    @endphp

    <div id="via-factory-v03-host" aria-label="VIA · Supervisora da Factory"></div>

    <script>
        window.VIA_FACTORY_CONFIG = {{ Illuminate\Support\Js::from($viaFactoryConfig) }};
        (() => {
            const config = window.VIA_FACTORY_CONFIG;
            const host = document.getElementById('via-factory-v03-host');
            if (!config || !host) return;

            // A VIA agora vive diretamente no DOM da Factory. Não existe iframe,
            // portanto não existe canvas/página secundária capaz de produzir quadro branco.
            host.style.position = 'fixed';
            host.style.inset = '0';
            host.style.zIndex = '2147483000';
            host.style.pointerEvents = 'none';
            host.style.background = 'transparent';

            const currentContext = () => ({
                module: 'Factory',
                project: 'VitrineAI-Factory',
                title: document.title,
                path: `${window.location.pathname}${window.location.search}${window.location.hash}`,
                url: window.location.href,
            });

            document.body.dataset.viaModule = 'Factory';
            document.body.dataset.viaProject = 'VitrineAI-Factory';

            const nativeFetch = window.fetch.bind(window);
            if (!window.__VIA_FACTORY_FETCH_BRIDGE__) {
                window.__VIA_FACTORY_FETCH_BRIDGE__ = true;
                window.fetch = async (input, init) => {
                    const requestUrl = typeof input === 'string'
                        ? input
                        : input instanceof URL
                            ? input.toString()
                            : input?.url || '';

                    const isViaChat = requestUrl === '/api/via'
                        || requestUrl === `${config.viaOrigin}/api/via`
                        || requestUrl.endsWith('/api/via');

                    if (!isViaChat) return nativeFetch(input, init);

                    try {
                        const rawBody = typeof init?.body === 'string' ? init.body : '{}';
                        const payload = JSON.parse(rawBody || '{}');
                        return nativeFetch(config.chatUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': config.csrfToken,
                            },
                            body: JSON.stringify({
                                ...payload,
                                context: { ...currentContext(), ...(payload.context || {}) },
                            }),
                        });
                    } catch (error) {
                        return new Response(JSON.stringify({
                            error: error instanceof Error ? error.message : 'Falha de comunicação com a Factory.',
                        }), {
                            status: 502,
                            headers: { 'Content-Type': 'application/json; charset=utf-8' },
                        });
                    }
                };
            }

            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = `${config.widgetCss}?v=${encodeURIComponent(config.version)}`;
            css.dataset.viaCanonicalAsset = 'css';
            document.head.appendChild(css);

            const script = document.createElement('script');
            script.src = `${config.widgetJs}?v=${encodeURIComponent(config.version)}`;
            script.defer = true;
            script.dataset.viaCanonicalAsset = 'js';
            script.onload = () => {
                const mount = () => {
                    if (!window.VIA?.mount) return;
                    window.VIA.unmount?.();
                    window.VIA.mount(host);
                    window.VIA.setContext?.({ module: 'Factory', project: 'VitrineAI-Factory' });
                    const widget = host.querySelector('.via-widget');
                    if (widget) widget.style.pointerEvents = 'none';
                    host.querySelectorAll('.via-widget > *').forEach((element) => {
                        element.style.pointerEvents = 'auto';
                    });
                };
                window.requestAnimationFrame(mount);
            };
            script.onerror = () => console.error('[VIA Factory] Falha ao carregar bundle canônico da VIA.');
            document.body.appendChild(script);
        })();
    </script>
@endif
