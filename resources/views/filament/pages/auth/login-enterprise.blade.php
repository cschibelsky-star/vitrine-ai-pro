<x-filament-panels::page.simple>
    <div class="vai-login-shell">
        <section class="vai-login-brand">
            <div class="vai-login-brand-inner">
                <div class="vai-login-logo-row">
                    <div class="vai-login-mark">V</div>
                    <div>
                        <div class="vai-login-wordmark">VITRINE</div>
                        <div class="vai-login-submark">AI PRO</div>
                    </div>
                </div>

                <div class="vai-login-tag">CENTRO OPERACIONAL</div>
                <h1>Vitrine IA Pro Enterprise</h1>
                <p>IA, SaaS, Factory, clientes, produtos, licenças e operação do ecossistema conectados em uma única plataforma.</p>

                <div class="vai-login-orbit" aria-hidden="true">
                    <div class="vai-orbit-ring"></div>
                    <div class="vai-orbit-core">V</div>
                    <div class="vai-orbit-node vai-node-top"><span>◎</span><small>FACTORY</small></div>
                    <div class="vai-orbit-node vai-node-left"><span>••</span><small>CLIENTES</small></div>
                    <div class="vai-orbit-node vai-node-right"><span>▦</span><small>ANALYTICS</small></div>
                    <div class="vai-orbit-node vai-node-bottom-left"><span>$</span><small>FINANCEIRO</small></div>
                    <div class="vai-orbit-node vai-node-bottom-right"><span>AI</span><small>IA CENTER</small></div>
                </div>
            </div>
        </section>

        <section class="vai-login-access">
            <div class="vai-login-access-inner">
                <div class="vai-login-shield">◇</div>
                <h2>Acessar Plataforma</h2>
                <p>Entre com suas credenciais para continuar</p>
                <div class="vai-login-form">
                    <form wire:submit="authenticate">
                        {{ $this->form }}
                        <x-filament::button type="submit" form="authenticate" class="vai-login-submit">
                            Entrar
                        </x-filament::button>
                    </form>
                </div>
                <div class="vai-login-legal">Ao entrar, você concorda com nossos<br><span>Termos de Uso</span> e <span>Política de Privacidade</span>.</div>
            </div>
        </section>
    </div>
</x-filament-panels::page.simple>
