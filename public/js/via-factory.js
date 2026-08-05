(() => {
    'use strict';

    const config = window.VIA_FACTORY_CONFIG;
    const root = document.getElementById('via-factory-root');
    if (!config || !root || root.dataset.mounted === 'true') return;

    root.dataset.mounted = 'true';
    root.dataset.state = 'online';

    const storageKey = `via.factory.messages.${config.user?.id || 'admin'}`;
    const openKey = `via.factory.open.${config.user?.id || 'admin'}`;
    const voiceKey = `via.factory.voice.${config.user?.id || 'admin'}`;
    const sessionKey = `via.factory.session.${config.user?.id || 'admin'}`;
    const sessionId = localStorage.getItem(sessionKey) || `factory-${config.user?.id || 'admin'}-${Date.now().toString(36)}`;
    localStorage.setItem(sessionKey, sessionId);

    const safeParse = (value, fallback) => {
        try { return value ? JSON.parse(value) : fallback; } catch { return fallback; }
    };

    const state = {
        open: safeParse(localStorage.getItem(openKey), false),
        voice: safeParse(localStorage.getItem(voiceKey), true),
        busy: false,
        context: null,
        pendingConfirmation: null,
        messages: safeParse(localStorage.getItem(storageKey), []),
    };

    if (!Array.isArray(state.messages) || state.messages.length === 0) {
        state.messages = [{
            role: 'assistant',
            content: `Olá, ${config.user?.name || 'Cristian'}. Eu sou a VIA, supervisora da Factory. Posso consultar projetos, execuções, produção e saúde do ecossistema, além de acionar capacidades autorizadas. O que vamos fazer?`,
        }];
    }

    root.innerHTML = `
        <div class="via-factory-shell">
            <section class="via-factory-panel" aria-label="VIA — Supervisora da Factory" aria-hidden="true">
                <header class="via-factory-header">
                    <div class="via-factory-identity">
                        <div class="via-factory-mini-core">VIA</div>
                        <div class="via-factory-title">
                            <strong>VIA · Supervisora da Factory</strong>
                            <small><span data-via-state-label>Online</span> · operação autenticada</small>
                        </div>
                    </div>
                    <div class="via-factory-header-actions">
                        <button type="button" class="via-factory-icon" data-via-refresh title="Atualizar contexto">↻</button>
                        <button type="button" class="via-factory-icon" data-via-voice title="Ativar ou desativar voz">🔊</button>
                        <button type="button" class="via-factory-icon" data-via-close title="Minimizar">—</button>
                    </div>
                </header>
                <div class="via-factory-context" data-via-context>
                    <span class="via-factory-connection">Factory conectada</span><span data-via-page>Carregando contexto...</span>
                </div>
                <div class="via-factory-messages" data-via-messages></div>
                <div class="via-factory-typing" data-via-typing><span>VIA está processando</span><i></i><i></i><i></i></div>
                <div class="via-factory-shortcuts">
                    <button type="button" class="via-factory-shortcut" data-prompt="Qual é o status da Factory?">Status da Factory</button>
                    <button type="button" class="via-factory-shortcut" data-prompt="Mostre os projetos da Factory">Projetos</button>
                    <button type="button" class="via-factory-shortcut" data-prompt="Mostre as execuções recentes">Execuções</button>
                    <button type="button" class="via-factory-shortcut" data-prompt="Como está a produção e a release?">Produção</button>
                    <button type="button" class="via-factory-shortcut" data-prompt="Como está o ecossistema e os serviços?">Ecossistema</button>
                </div>
                <form class="via-factory-composer" data-via-form>
                    <button type="button" class="via-factory-icon" data-via-mic title="Falar com a VIA">🎙</button>
                    <textarea class="via-factory-input" data-via-input rows="1" maxlength="20000" placeholder="Converse com a VIA sobre a Factory..."></textarea>
                    <button type="submit" class="via-factory-send" data-via-send>Enviar</button>
                </form>
            </section>
            <div class="via-factory-toast" data-via-toast role="status"></div>
            <button type="button" class="via-factory-launcher" data-via-launcher aria-label="Abrir VIA" aria-expanded="false">
                <span class="via-factory-halo"></span>
                <span class="via-factory-core">VIA</span>
                <span class="via-factory-beam"></span>
                <span class="via-factory-base"></span>
                <span class="via-factory-state-dot"></span>
                <span class="via-factory-label">Supervisora</span>
            </button>
        </div>`;

    const panel = root.querySelector('.via-factory-panel');
    const launcher = root.querySelector('[data-via-launcher]');
    const closeButton = root.querySelector('[data-via-close]');
    const refreshButton = root.querySelector('[data-via-refresh]');
    const voiceButton = root.querySelector('[data-via-voice]');
    const micButton = root.querySelector('[data-via-mic]');
    const form = root.querySelector('[data-via-form]');
    const input = root.querySelector('[data-via-input]');
    const sendButton = root.querySelector('[data-via-send]');
    const messagesNode = root.querySelector('[data-via-messages]');
    const typingNode = root.querySelector('[data-via-typing]');
    const stateLabel = root.querySelector('[data-via-state-label]');
    const pageNode = root.querySelector('[data-via-page]');
    const toastNode = root.querySelector('[data-via-toast]');
    let toastTimer = null;

    const stateLabels = {
        online: 'Online',
        listening: 'Ouvindo',
        thinking: 'Pensando',
        executing: 'Executando',
        success: 'Concluído',
        error: 'Atenção',
    };

    function setVisualState(next) {
        root.dataset.state = next;
        stateLabel.textContent = stateLabels[next] || next;
        window.dispatchEvent(new CustomEvent('via:state-change', { detail: { state: next, source: 'factory' } }));
    }

    function setOpen(open) {
        state.open = Boolean(open);
        panel.classList.toggle('is-open', state.open);
        panel.setAttribute('aria-hidden', String(!state.open));
        launcher.setAttribute('aria-expanded', String(state.open));
        localStorage.setItem(openKey, JSON.stringify(state.open));
        if (state.open) {
            loadContext(false);
            setTimeout(() => input.focus(), 80);
        }
    }

    function showToast(message) {
        toastNode.textContent = message;
        toastNode.classList.add('is-visible');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastNode.classList.remove('is-visible'), 3000);
    }

    function saveMessages() {
        state.messages = state.messages.slice(-50);
        localStorage.setItem(storageKey, JSON.stringify(state.messages));
    }

    function addMessage(role, content, persist = true) {
        const message = { role, content: String(content || '') };
        state.messages.push(message);
        if (persist) saveMessages();
        renderMessage(message);
        scrollMessages();
        return message;
    }

    function renderMessage(message) {
        const bubble = document.createElement('div');
        bubble.className = `via-factory-message ${message.role === 'user' ? 'user' : message.role === 'system' ? 'system' : 'assistant'}`;
        bubble.textContent = message.content;
        messagesNode.appendChild(bubble);
    }

    function renderMessages() {
        messagesNode.innerHTML = '';
        state.messages.forEach(renderMessage);
        scrollMessages();
    }

    function scrollMessages() {
        requestAnimationFrame(() => { messagesNode.scrollTop = messagesNode.scrollHeight; });
    }

    function renderConfirmation(action, payload, message) {
        state.pendingConfirmation = { action, payload };
        const card = document.createElement('div');
        card.className = 'via-factory-confirmation';
        const text = document.createElement('p');
        text.textContent = message || 'Esta ação altera a Factory e exige confirmação explícita.';
        const actions = document.createElement('div');
        actions.className = 'via-factory-confirmation-actions';
        const confirm = document.createElement('button');
        confirm.type = 'button';
        confirm.className = 'via-factory-confirm';
        confirm.textContent = 'Confirmar execução';
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'via-factory-cancel';
        cancel.textContent = 'Cancelar';
        actions.append(confirm, cancel);
        card.append(text, actions);
        messagesNode.appendChild(card);
        scrollMessages();

        confirm.addEventListener('click', async () => {
            confirm.disabled = true;
            cancel.disabled = true;
            await executeAction(action, payload, 'EXECUTAR');
            card.remove();
            state.pendingConfirmation = null;
        });
        cancel.addEventListener('click', () => {
            card.remove();
            state.pendingConfirmation = null;
            addMessage('system', 'Ação cancelada. Nenhuma alteração foi executada.');
        });
    }

    function currentPageContext() {
        const heading = document.querySelector('h1, .fi-header-heading, [data-page-title]');
        const resource = location.pathname.split('/').filter(Boolean).slice(-2).join('/');
        return {
            url: location.href,
            path: location.pathname,
            title: document.title,
            module: heading?.textContent?.trim() || 'Centro Operacional',
            resource,
        };
    }

    async function apiFetch(url, options = {}) {
        const headers = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(options.body ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrfToken } : {}),
            ...(options.headers || {}),
        };
        const response = await fetch(url, {
            credentials: 'same-origin',
            ...options,
            headers,
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(data.message || data.answer || `Falha HTTP ${response.status}`);
            error.status = response.status;
            error.data = data;
            throw error;
        }
        return data;
    }

    async function loadContext(announce = false) {
        try {
            const query = new URLSearchParams(currentPageContext()).toString();
            const data = await apiFetch(`${config.contextUrl}?${query}`);
            state.context = data.context;
            const factory = data.context?.factory || {};
            const ecosystem = data.context?.ecosystem || {};
            pageNode.textContent = `${currentPageContext().module} · ${factory.projects_total || 0} projetos · ecossistema ${ecosystem.status || 'indisponível'}`;
            if (announce) addMessage('system', 'Contexto operacional atualizado.');
            setVisualState('online');
            return data.context;
        } catch (error) {
            pageNode.textContent = 'Factory conectada · contexto parcial';
            if (announce) addMessage('system', `Não consegui atualizar todo o contexto: ${error.message}`);
            return null;
        }
    }

    function setBusy(busy) {
        state.busy = busy;
        input.disabled = busy;
        sendButton.disabled = busy;
        micButton.disabled = busy;
        refreshButton.disabled = busy;
        typingNode.classList.toggle('is-visible', busy);
    }

    function speak(text) {
        if (!state.voice || !('speechSynthesis' in window)) return;
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(String(text).replace(/[*#`]/g, ''));
        utterance.lang = 'pt-BR';
        utterance.rate = 0.98;
        const voices = window.speechSynthesis.getVoices();
        const preferred = voices.find(voice => /^pt-BR/i.test(voice.lang)) || voices.find(voice => /^pt/i.test(voice.lang));
        if (preferred) utterance.voice = preferred;
        utterance.onend = () => setVisualState('online');
        utterance.onerror = () => setVisualState('online');
        utterance.onstart = () => setVisualState('success');
        window.speechSynthesis.speak(utterance);
    }

    async function sendMessage(raw) {
        const message = String(raw ?? input.value).trim();
        if (!message || state.busy) return;
        input.value = '';
        input.style.height = 'auto';
        addMessage('user', message);
        setBusy(true);
        setVisualState('thinking');

        try {
            const data = await apiFetch(config.chatUrl, {
                method: 'POST',
                body: JSON.stringify({
                    message,
                    history: state.messages.filter(item => item.role !== 'system').slice(0, -1).slice(-20),
                    sessionId,
                    context: currentPageContext(),
                }),
            });
            const answer = data.answer || 'A Factory respondeu sem conteúdo textual.';
            addMessage('assistant', answer);
            if (data.requires_confirmation && data.action) {
                renderConfirmation(data.action, data.payload || {}, answer);
                setVisualState('executing');
            } else {
                setVisualState(data.ok === false ? 'error' : 'success');
                speak(answer);
                setTimeout(() => setVisualState('online'), 1800);
            }
        } catch (error) {
            setVisualState('error');
            if (error.status === 419) {
                addMessage('assistant', 'Sua sessão expirou. Atualize a página e entre novamente para continuar com segurança.');
            } else {
                addMessage('assistant', `Não consegui concluir agora. ${error.message}`);
            }
            setTimeout(() => setVisualState('online'), 2600);
        } finally {
            setBusy(false);
            input.focus();
        }
    }

    async function executeAction(action, payload = {}, confirm = '') {
        setBusy(true);
        setVisualState('executing');
        try {
            const data = await apiFetch(config.actionUrl, {
                method: 'POST',
                body: JSON.stringify({ action, payload, confirm }),
            });
            const answer = data.answer || 'Ação concluída.';
            addMessage('assistant', answer);
            setVisualState(data.ok === false ? 'error' : 'success');
            speak(answer);
            await loadContext(false);
            setTimeout(() => setVisualState('online'), 2000);
            return data;
        } catch (error) {
            if (error.status === 409 && error.data?.requires_confirmation) {
                renderConfirmation(error.data.action, error.data.payload || payload, error.data.answer);
                return null;
            }
            setVisualState('error');
            addMessage('assistant', `A ação não foi concluída. ${error.message}`);
            setTimeout(() => setVisualState('online'), 2600);
            return null;
        } finally {
            setBusy(false);
        }
    }

    function startRecognition() {
        const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!Recognition) {
            showToast('Reconhecimento de voz indisponível neste navegador.');
            addMessage('system', 'Use o campo de texto; o navegador não disponibilizou o microfone para reconhecimento de voz.');
            return;
        }
        const recognition = new Recognition();
        recognition.lang = 'pt-BR';
        recognition.interimResults = false;
        recognition.continuous = false;
        recognition.onstart = () => setVisualState('listening');
        recognition.onresult = event => {
            const transcript = event.results?.[0]?.[0]?.transcript || '';
            input.value = transcript;
            sendMessage(transcript);
        };
        recognition.onerror = () => {
            setVisualState('error');
            showToast('Não consegui compreender o áudio.');
        };
        recognition.onend = () => {
            if (!state.busy) setVisualState('online');
        };
        try { recognition.start(); } catch { showToast('Não foi possível iniciar o microfone.'); }
    }

    launcher.addEventListener('click', () => setOpen(!state.open));
    closeButton.addEventListener('click', () => setOpen(false));
    refreshButton.addEventListener('click', () => loadContext(true));
    voiceButton.addEventListener('click', () => {
        state.voice = !state.voice;
        localStorage.setItem(voiceKey, JSON.stringify(state.voice));
        voiceButton.textContent = state.voice ? '🔊' : '🔇';
        showToast(state.voice ? 'Voz ativada.' : 'Voz desativada.');
        if (!state.voice) window.speechSynthesis?.cancel();
    });
    micButton.addEventListener('click', startRecognition);
    form.addEventListener('submit', event => {
        event.preventDefault();
        sendMessage();
    });
    input.addEventListener('keydown', event => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });
    input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 110)}px`;
    });
    root.querySelectorAll('[data-prompt]').forEach(button => {
        button.addEventListener('click', () => sendMessage(button.dataset.prompt));
    });

    document.addEventListener('livewire:navigated', () => {
        pageNode.textContent = `${currentPageContext().module} · atualizando contexto...`;
        loadContext(false);
    });

    voiceButton.textContent = state.voice ? '🔊' : '🔇';
    renderMessages();
    setOpen(state.open);
    loadContext(false);

    const api = {
        version: config.version || '1.0.0',
        open: () => setOpen(true),
        close: () => setOpen(false),
        toggle: () => setOpen(!state.open),
        send: sendMessage,
        refreshContext: () => loadContext(true),
        execute: (action, payload = {}) => executeAction(action, payload),
        getContext: () => state.context,
        setState: setVisualState,
    };
    window.VIAWidget = api;
    window.VIAFactory = api;
    window.dispatchEvent(new CustomEvent('via:ready', { detail: { source: 'factory', version: api.version } }));
})();
