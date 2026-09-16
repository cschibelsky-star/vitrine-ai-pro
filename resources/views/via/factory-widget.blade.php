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

            const normalizeText = (value, max = 240) => String(value || '')
                .replace(/\s+/g, ' ')
                .trim()
                .slice(0, max);

            const isVisible = (element) => {
                if (!(element instanceof HTMLElement)) return false;
                if (element.closest('#via-factory-v03-host, .via-widget')) return false;
                const style = window.getComputedStyle(element);
                if (style.display === 'none' || style.visibility === 'hidden' || Number(style.opacity) === 0) return false;
                const rect = element.getBoundingClientRect();
                return rect.width > 0 && rect.height > 0;
            };

            const uniqueVisibleText = (selector, limit = 30, max = 240) => {
                const values = [];
                const seen = new Set();
                document.querySelectorAll(selector).forEach((element) => {
                    if (values.length >= limit || !isVisible(element)) return;
                    const text = normalizeText(element.getAttribute('aria-label') || element.textContent, max);
                    if (!text || seen.has(text)) return;
                    seen.add(text);
                    values.push(text);
                });
                return values;
            };

            const collectTables = () => Array.from(document.querySelectorAll('table'))
                .filter(isVisible)
                .slice(0, 4)
                .map((table) => {
                    const headings = Array.from(table.querySelectorAll('thead th'))
                        .filter(isVisible)
                        .slice(0, 10)
                        .map((cell) => normalizeText(cell.textContent, 120))
                        .filter(Boolean);
                    const rows = Array.from(table.querySelectorAll('tbody tr'))
                        .filter(isVisible)
                        .slice(0, 8)
                        .map((row) => Array.from(row.querySelectorAll('th,td'))
                            .filter(isVisible)
                            .slice(0, 10)
                            .map((cell) => normalizeText(cell.textContent, 160))
                            .filter(Boolean))
                        .filter((row) => row.length);
                    return { headings, rows };
                });

            const collectCards = () => Array.from(document.querySelectorAll('main article,main section,.fi-section,.fi-wi-stats-overview-stat,[data-via-card]'))
                .filter(isVisible)
                .slice(0, 20)
                .map((card) => {
                    const title = normalizeText(card.querySelector('h1,h2,h3,h4,[role="heading"],.fi-section-header-heading')?.textContent, 160);
                    const text = normalizeText(card.textContent, 420);
                    const status = normalizeText(card.querySelector('[data-status],.fi-badge,.badge,[class*="status"]')?.textContent, 120);
                    return { title, text, status };
                })
                .filter((card) => card.title || card.text || card.status);

            const collectMetrics = () => Array.from(document.querySelectorAll('.fi-wi-stats-overview-stat,[data-via-metric]'))
                .filter(isVisible)
                .slice(0, 20)
                .map((metric) => ({
                    label: normalizeText(metric.querySelector('[class*="label"],h3,h4')?.textContent, 140),
                    value: normalizeText(metric.querySelector('[class*="value"],strong,b')?.textContent, 120),
                    description: normalizeText(metric.querySelector('[class*="description"],small,p')?.textContent, 180),
                }))
                .filter((metric) => metric.label || metric.value);

            const arrayDiff = (before = [], after = []) => ({
                added: after.filter((item) => !before.includes(item)).slice(0, 12),
                removed: before.filter((item) => !after.includes(item)).slice(0, 12),
            });

            const snapshotKey = () => `via.factory.screen.v1:${window.location.pathname}`;

            const screenSignature = (screen) => JSON.stringify({
                headings: screen.headings,
                controls: screen.controls,
                alerts: screen.alerts,
                badges: screen.badges,
                metrics: screen.metrics,
                tables: screen.tables,
            });

            const collectScreenContext = () => {
                const current = {
                    headings: uniqueVisibleText('h1,h2,h3,[role="heading"]', 24, 180),
                    texts: uniqueVisibleText('main p,main li,main dt,main dd,[data-via-context]', 40, 260),
                    controls: uniqueVisibleText('button,[role="button"],a.fi-btn', 30, 160),
                    links: Array.from(document.querySelectorAll('a[href]'))
                        .filter(isVisible)
                        .slice(0, 30)
                        .map((link) => ({
                            label: normalizeText(link.getAttribute('aria-label') || link.textContent, 140),
                            href: String(link.getAttribute('href') || '').slice(0, 240),
                        }))
                        .filter((item) => item.label),
                    alerts: uniqueVisibleText('[role="alert"],.alert,.fi-notification,.notification', 12, 260),
                    badges: uniqueVisibleText('.fi-badge,.badge,[data-status],[class*="status"]', 24, 140),
                    cards: collectCards(),
                    metrics: collectMetrics(),
                    tables: collectTables(),
                    capturedAt: new Date().toISOString(),
                };

                window.__VIA_SCREEN_MEMORY__ = window.__VIA_SCREEN_MEMORY__ || {};
                const runtimeMemory = window.__VIA_SCREEN_MEMORY__[snapshotKey()] || null;
                let persistedMemory = null;
                try { persistedMemory = JSON.parse(localStorage.getItem(snapshotKey()) || 'null'); } catch { persistedMemory = null; }
                const memory = runtimeMemory || persistedMemory;
                const memorySource = runtimeMemory ? 'runtime' : persistedMemory ? 'localStorage' : 'none';
                const previous = memory?.current || null;
                const changed = previous ? screenSignature(previous) !== screenSignature(current) : false;
                let lastChange = memory?.lastChange || null;

                if (changed) {
                    lastChange = {
                        changed: true,
                        detectedAt: current.capturedAt,
                        headings: arrayDiff(previous.headings || [], current.headings || []),
                        controls: arrayDiff(previous.controls || [], current.controls || []),
                        alerts: arrayDiff(previous.alerts || [], current.alerts || []),
                        badges: arrayDiff(previous.badges || [], current.badges || []),
                        metricsChanged: JSON.stringify(previous.metrics || []) !== JSON.stringify(current.metrics || []),
                        tablesChanged: JSON.stringify(previous.tables || []) !== JSON.stringify(current.tables || []),
                    };
                }

                const nextMemory = {
                    current,
                    previous: changed ? previous : (memory?.previous || null),
                    lastChange,
                    updatedAt: current.capturedAt,
                };

                window.__VIA_SCREEN_MEMORY__[snapshotKey()] = nextMemory;

                try {
                    localStorage.setItem(snapshotKey(), JSON.stringify(nextMemory));
                } catch {}

                return {
                    ...current,
                    memory: {
                        hasPrevious: Boolean(previous),
                        changedNow: changed,
                        lastChange,
                        memorySource,
                    },
                };
            };

            const currentContext = () => ({
                module: 'Factory',
                project: 'VitrineAI-Factory',
                title: document.title,
                path: `${window.location.pathname}${window.location.search}${window.location.hash}`,
                url: window.location.href,
                screen: collectScreenContext(),
            });

            document.body.dataset.viaModule = 'Factory';
            document.body.dataset.viaProject = 'VitrineAI-Factory';

            const naturalizeSpeech = (value) => normalizeText(value, 1800)
                .replace(/https?:\/\/\S+/gi, '')
                .replace(/\bRota:\s*\S+/gi, '')
                .replace(/\|/g, ', ')
                .replace(/[•▪◦]/g, ', ')
                .replace(/\s*:\s*/g, '. ')
                .replace(/\s*;\s*/g, '. ')
                .replace(/\bNovos controles\.\s*/gi, 'Agora estão disponíveis ')
                .replace(/\bRemovidos controles\.\s*/gi, 'A opção anterior removida foi ')
                .replace(/\s+/g, ' ')
                .trim();

            if ('speechSynthesis' in window && !window.__VIA_NATURAL_SPEECH__) {
                window.__VIA_NATURAL_SPEECH__ = true;
                const nativeSpeak = window.speechSynthesis.speak.bind(window.speechSynthesis);
                window.speechSynthesis.speak = (utterance) => {
                    if (utterance && typeof utterance.text === 'string') {
                        utterance.text = naturalizeSpeech(utterance.text);
                        utterance.lang = 'pt-BR';
                        utterance.rate = 0.96;
                        utterance.pitch = 1.02;
                    }
                    return nativeSpeak(utterance);
                };
            }

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
