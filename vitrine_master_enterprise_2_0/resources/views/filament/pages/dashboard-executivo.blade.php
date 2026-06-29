<x-filament-panels::page>
    <div class="vai-shell">
        <section class="vai-hero">
            <span class="vai-kicker">Vitrine AI Pro Master 2.0</span>
            <h1>Dashboard Executivo</h1>
            <p>Visão geral inteligente do ecossistema: clientes, produtos, licenças, receita, IA, Factory, atividades recentes e alertas operacionais em uma única tela.</p>
            <div class="vai-tags"><span class="vai-tag">SaaS Operacional</span><span class="vai-tag">Multiempresa</span><span class="vai-tag">IA Integrada</span><span class="vai-tag">Factory</span></div>
        </section>

        <div class="vai-grid vai-grid-4">
            @foreach($metrics as $metric)
                <div class="vai-card">
                    <div class="vai-card-head"><div><p class="vai-card-title">{{ $metric['label'] }}</p><div class="vai-metric">{{ $metric['value'] }}</div><p class="vai-muted"><span class="vai-up">{{ $metric['delta'] }}</span></p></div><div class="vai-icon">{{ $metric['icon'] }}</div></div>
                </div>
            @endforeach
        </div>

        <div class="vai-grid vai-grid-3" style="margin-top:18px">
            <div class="vai-card">
                <h3 class="vai-panel-title">Visão Geral do Ecossistema</h3>
                <div class="vai-list">
                    <div class="vai-list-item"><span>TV Digital Enterprise</span><strong>40%</strong></div>
                    <div class="vai-list-item"><span>Guia Digital da Cidade</span><strong>24%</strong></div>
                    <div class="vai-list-item"><span>Portal News AI</span><strong>16%</strong></div>
                    <div class="vai-list-item"><span>Município Digital IA</span><strong>12%</strong></div>
                    <div class="vai-list-item"><span>SISMED</span><strong>8%</strong></div>
                </div>
            </div>
            <div class="vai-card">
                <h3 class="vai-panel-title">Atividades Recentes</h3>
                <div class="vai-list">
                    <div class="vai-list-item"><span>Nova licença ativada<br><small class="vai-muted">TV Sumaré Enterprise</small></span><strong>2 min</strong></div>
                    <div class="vai-list-item"><span>Novo lead cadastrado<br><small class="vai-muted">Prefeitura de Paulínia</small></span><strong>15 min</strong></div>
                    <div class="vai-list-item"><span>Pagamento recebido<br><small class="vai-muted">R$ 1.500,00</small></span><strong>45 min</strong></div>
                    <div class="vai-list-item"><span>Projeto gerado<br><small class="vai-muted">Guia Digital</small></span><strong>1h</strong></div>
                </div>
            </div>
            <div class="vai-card">
                <h3 class="vai-panel-title">Acesso Rápido</h3>
                <div class="vai-list">
                    <a class="vai-list-item" href="/admin/clients"><span>Novo Cliente<br><small class="vai-muted">Cadastrar empresa/cliente</small></span><strong>＋</strong></a>
                    <a class="vai-list-item" href="/admin/licenses"><span>Nova Licença<br><small class="vai-muted">Ativar nova licença</small></span><strong>＋</strong></a>
                    <a class="vai-list-item" href="/admin/leads"><span>Novo Lead<br><small class="vai-muted">Cadastrar oportunidade</small></span><strong>＋</strong></a>
                    <a class="vai-list-item" href="/admin/factory-dashboard"><span>Factory<br><small class="vai-muted">Abrir ambiente de criação</small></span><strong>→</strong></a>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
