<x-filament-panels::page>
    <style>
        .via-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 1.25rem;
        }

        .via-panel,
        .via-side {
            border: 1px solid rgba(59, 130, 246, .18);
            border-radius: 24px;
            background: linear-gradient(145deg, rgba(15, 23, 42, .98), rgba(30, 41, 59, .96));
            box-shadow: 0 24px 60px rgba(15, 23, 42, .18);
            overflow: hidden;
        }

        .via-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(148, 163, 184, .14);
        }

        .via-identity {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .via-core {
            position: relative;
            width: 58px;
            height: 58px;
            border-radius: 999px;
            background: radial-gradient(circle at 35% 30%, #fef08a 0%, #38bdf8 38%, #1d4ed8 72%, #0f172a 100%);
            box-shadow: 0 0 0 8px rgba(56, 189, 248, .08), 0 0 38px rgba(56, 189, 248, .55);
            animation: viaPulse 2.8s ease-in-out infinite;
        }

        .via-core::after {
            content: '';
            position: absolute;
            inset: 7px;
            border: 1px solid rgba(255,255,255,.45);
            border-radius: inherit;
        }

        @keyframes viaPulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 8px rgba(56, 189, 248, .08), 0 0 38px rgba(56, 189, 248, .48); }
            50% { transform: scale(1.045); box-shadow: 0 0 0 12px rgba(56, 189, 248, .05), 0 0 52px rgba(250, 204, 21, .30); }
        }

        .via-title { color: #f8fafc; font-size: 1.05rem; font-weight: 700; }
        .via-subtitle { color: #94a3b8; font-size: .84rem; margin-top: .15rem; }

        .via-status {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border-radius: 999px;
            padding: .45rem .75rem;
            background: rgba(34, 197, 94, .10);
            color: #86efac;
            font-size: .76rem;
            font-weight: 700;
        }

        .via-status::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 14px rgba(34, 197, 94, .8);
        }

        .via-messages {
            min-height: 460px;
            max-height: 62vh;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .via-message {
            max-width: 82%;
            padding: .9rem 1rem;
            border-radius: 18px;
            line-height: 1.55;
            font-size: .92rem;
        }

        .via-message-assistant {
            align-self: flex-start;
            color: #e2e8f0;
            background: rgba(30, 64, 175, .23);
            border: 1px solid rgba(96, 165, 250, .15);
            border-bottom-left-radius: 6px;
        }

        .via-message-user {
            align-self: flex-end;
            color: #0f172a;
            background: linear-gradient(135deg, #facc15, #fde68a);
            border-bottom-right-radius: 6px;
        }

        .via-composer {
            padding: 1rem 1.25rem 1.25rem;
            border-top: 1px solid rgba(148, 163, 184, .14);
        }

        .via-form {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: .75rem;
            align-items: center;
        }

        .via-input {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, .22);
            border-radius: 16px;
            background: rgba(15, 23, 42, .72);
            color: #f8fafc;
            padding: .9rem 1rem;
            outline: none;
        }

        .via-input:focus { border-color: rgba(56, 189, 248, .75); box-shadow: 0 0 0 3px rgba(56, 189, 248, .10); }

        .via-button {
            border: 0;
            border-radius: 14px;
            min-width: 46px;
            height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-weight: 700;
        }

        .via-button-mic { background: rgba(56, 189, 248, .12); color: #7dd3fc; }
        .via-button-send { background: linear-gradient(135deg, #2563eb, #38bdf8); color: white; padding: 0 1rem; }

        .via-side { padding: 1.25rem; color: #cbd5e1; }
        .via-side h3 { color: #f8fafc; font-size: .95rem; font-weight: 700; margin-bottom: 1rem; }
        .via-card { padding: .9rem; border-radius: 16px; background: rgba(15, 23, 42, .62); border: 1px solid rgba(148, 163, 184, .12); margin-bottom: .75rem; }
        .via-card strong { display: block; color: #f8fafc; font-size: .82rem; margin-bottom: .25rem; }
        .via-card span { display: block; color: #94a3b8; font-size: .76rem; line-height: 1.45; }

        @media (max-width: 1024px) {
            .via-shell { grid-template-columns: 1fr; }
            .via-side { display: none; }
            .via-messages { min-height: 420px; }
        }

        @media (max-width: 640px) {
            .via-header { align-items: flex-start; padding: 1rem; }
            .via-status { display: none; }
            .via-messages { padding: 1rem; min-height: 360px; }
            .via-message { max-width: 92%; }
            .via-composer { padding: .85rem; }
            .via-button-send { padding: 0 .85rem; }
        }
    </style>

    <div class="via-shell">
        <section class="via-panel">
            <header class="via-header">
                <div class="via-identity">
                    <div class="via-core" aria-hidden="true"></div>
                    <div>
                        <div class="via-title">VIA — Assistente da Vitrine IA Pro</div>
                        <div class="via-subtitle">Ambiente de homologação · primeira versão funcional</div>
                    </div>
                </div>
                <div class="via-status">Disponível</div>
            </header>

            <div class="via-messages" id="via-messages">
                @foreach ($messages as $item)
                    <div class="via-message {{ $item['role'] === 'user' ? 'via-message-user' : 'via-message-assistant' }}">
                        {{ $item['content'] }}
                    </div>
                @endforeach
            </div>

            <div class="via-composer">
                <form wire:submit="sendMessage" class="via-form">
                    <button
                        type="button"
                        class="via-button via-button-mic"
                        title="Reconhecimento de voz será conectado na próxima etapa"
                        aria-label="Ativar microfone"
                    >
                        <x-heroicon-o-microphone class="h-5 w-5" />
                    </button>

                    <input
                        type="text"
                        wire:model="message"
                        class="via-input"
                        placeholder="Converse com a VIA..."
                        autocomplete="off"
                    />

                    <button type="submit" class="via-button via-button-send">
                        <span class="hidden sm:inline">Enviar</span>
                        <x-heroicon-o-paper-airplane class="h-5 w-5 sm:ml-2" />
                    </button>
                </form>
            </div>
        </section>

        <aside class="via-side">
            <h3>Status da VIA</h3>

            <div class="via-card">
                <strong>Modo atual</strong>
                <span>Homologação protegida por feature flag.</span>
            </div>

            <div class="via-card">
                <strong>Chat</strong>
                <span>Interface e envio local ativos.</span>
            </div>

            <div class="via-card">
                <strong>Roteador de IA</strong>
                <span>Preparado para conexão com /api/flow/ai/route.</span>
            </div>

            <div class="via-card">
                <strong>Voz</strong>
                <span>Botão incluído; reconhecimento será ativado na etapa seguinte.</span>
            </div>
        </aside>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('commit', ({ succeed }) => {
                succeed(() => {
                    requestAnimationFrame(() => {
                        const messages = document.getElementById('via-messages');
                        if (messages) messages.scrollTop = messages.scrollHeight;
                    });
                });
            });
        });
    </script>
</x-filament-panels::page>
