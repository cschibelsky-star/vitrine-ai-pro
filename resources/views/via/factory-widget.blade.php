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
            'version' => '3.0.0',
            'viaUrl' => 'https://via.hml.vitrineiapro.com.br/',
        ];
    @endphp

    <div id="via-factory-v03-host" class="via-factory-v03-host" aria-label="VIA · Supervisora da Factory">
        <iframe
            id="via-factory-v03-frame"
            class="via-factory-v03-frame"
            title="VIA · Supervisora da Factory"
            allow="microphone"
            referrerpolicy="strict-origin-when-cross-origin"
        ></iframe>
    </div>

    <style>
        .via-factory-v03-host {
            position: fixed;
            inset: 0;
            z-index: 2147483000;
            pointer-events: none;
            overflow: hidden;
        }
        .via-factory-v03-frame {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: 0;
            background: transparent;
            pointer-events: none;
        }
        .via-factory-v03-host.is-open .via-factory-v03-frame {
            pointer-events: auto;
        }
        @media (max-width: 640px) {
            .via-factory-v03-host.is-open {
                pointer-events: auto;
            }
        }
    </style>

    <script>
        window.VIA_FACTORY_CONFIG = {{ Illuminate\Support\Js::from($viaFactoryConfig) }};
        (() => {
            const config = window.VIA_FACTORY_CONFIG;
            const frame = document.getElementById('via-factory-v03-frame');
            const host = document.getElementById('via-factory-v03-host');
            if (!config || !frame || !host) return;

            const viaOrigin = new URL(config.viaUrl).origin;
            const params = new URLSearchParams({
                embed: '1',
                hostOrigin: window.location.origin,
                viaModule: 'Factory',
                viaProject: 'VitrineAI-Factory',
            });
            frame.src = `${config.viaUrl}?${params.toString()}`;

            const currentContext = () => ({
                module: 'Factory',
                project: 'VitrineAI-Factory',
                title: document.title,
                path: `${window.location.pathname}${window.location.search}${window.location.hash}`,
                url: window.location.href,
            });

            window.addEventListener('message', async (event) => {
                if (event.source !== frame.contentWindow || event.origin !== viaOrigin) return;
                const data = event.data;
                if (!data || typeof data !== 'object') return;

                if (data.type === 'via:frame-state') {
                    host.classList.toggle('is-open', Boolean(data.open));
                    return;
                }

                if (data.type !== 'via:host:request' || !data.requestId) return;

                try {
                    const payload = data.payload && typeof data.payload === 'object' ? data.payload : {};
                    const response = await fetch(config.chatUrl, {
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
                    const result = await response.json();
                    frame.contentWindow.postMessage({
                        type: 'via:host:response',
                        requestId: data.requestId,
                        ok: response.ok,
                        payload: result,
                        error: response.ok ? undefined : (result.answer || result.message || result.error || 'Falha na VIA da Factory.'),
                    }, viaOrigin);
                } catch (error) {
                    frame.contentWindow.postMessage({
                        type: 'via:host:response',
                        requestId: data.requestId,
                        ok: false,
                        error: error instanceof Error ? error.message : 'Falha de comunicação com a Factory.',
                    }, viaOrigin);
                }
            });
        })();
    </script>
@endif
