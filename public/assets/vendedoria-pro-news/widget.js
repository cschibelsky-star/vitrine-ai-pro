(function () {
    if (window.__VendedorIAProNewsLoaded) {
        return;
    }

    window.__VendedorIAProNewsLoaded = true;

    const currentScript = document.currentScript;
    const scriptOrigin = currentScript && currentScript.src
        ? new URL(currentScript.src).origin
        : window.location.origin;

    const endpoint = currentScript?.dataset?.endpoint || (scriptOrigin + '/api/vendedoria-pro-news/leads');
    const cssHref = currentScript?.dataset?.css || (scriptOrigin + '/assets/vendedoria-pro-news/widget.css');

    function loadCss() {
        if (document.querySelector('link[data-vpnews-widget-css="true"]')) {
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = cssHref;
        link.dataset.vpnewsWidgetCss = 'true';
        document.head.appendChild(link);
    }

    function createWidget() {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'vpnews-widget-button';
        button.textContent = 'VendedorIA News';

        const panel = document.createElement('div');
        panel.className = 'vpnews-widget-panel';

        panel.innerHTML = `
            <div class="vpnews-widget-header">
                <strong>VendedorIA Pro News</strong>
                <span>Capte interessados em portal de notícias automatizado e envie direto ao Comercial Master.</span>
            </div>

            <div class="vpnews-widget-body">
                <form class="vpnews-widget-form">
                    <input name="empresa" placeholder="Empresa ou instituição">
                    <input name="contato" placeholder="Nome do contato *" required>
                    <input name="telefone" placeholder="WhatsApp">
                    <input name="email" type="email" placeholder="E-mail">
                    <input name="cidade" placeholder="Cidade">

                    <select name="plano_sugerido">
                        <option>Portal Automatizado</option>
                        <option>Portal Local</option>
                        <option>Licenciamento Institucional</option>
                        <option>Sob proposta</option>
                    </select>

                    <textarea name="observacoes" placeholder="Resumo do atendimento"></textarea>

                    <input name="website" style="display:none" tabindex="-1" autocomplete="off">

                    <label class="vpnews-widget-lgpd">
                        <input type="checkbox" name="consentimento_lgpd" required>
                        <span>Autorizo o contato comercial da VitrineIA Pro e o tratamento dos dados informados conforme a LGPD.</span>
                    </label>

                    <button class="vpnews-widget-submit" type="submit">Enviar lead</button>

                    <div class="vpnews-widget-status"></div>
                </form>
            </div>
        `;

        document.body.appendChild(button);
        document.body.appendChild(panel);

        button.addEventListener('click', function () {
            panel.classList.toggle('is-open');
        });

        const form = panel.querySelector('.vpnews-widget-form');
        const statusBox = panel.querySelector('.vpnews-widget-status');
        const submitButton = panel.querySelector('.vpnews-widget-submit');

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            statusBox.textContent = 'Enviando lead...';
            submitButton.disabled = true;
            submitButton.textContent = 'Enviando...';

            const data = Object.fromEntries(new FormData(form).entries());
            data.consentimento_lgpd = form.consentimento_lgpd.checked;
            data.pagina_origem = window.location.href || 'Widget VendedorIA Pro News';

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (!response.ok || !result.success) {
                    statusBox.textContent = 'Não foi possível enviar. Verifique os campos obrigatórios.';
                    submitButton.disabled = false;
                    submitButton.textContent = 'Enviar lead';
                    return;
                }

                statusBox.textContent = 'Lead enviado com sucesso. ID: ' + result.lead_id;
                form.reset();

                submitButton.disabled = false;
                submitButton.textContent = 'Enviar lead';
            } catch (error) {
                statusBox.textContent = 'Erro de conexão ao enviar o lead.';
                submitButton.disabled = false;
                submitButton.textContent = 'Enviar lead';
            }
        });
    }

    loadCss();

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createWidget);
    } else {
        createWidget();
    }
})();
