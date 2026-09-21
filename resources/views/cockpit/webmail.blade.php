<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Webmail | Cockpit Vitrine IA Pro</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#0B1020;color:#F8FAFC;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.shell{max-width:1440px;margin:0 auto;padding:32px}.topbar{display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:24px}.eyebrow{font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:#94A3B8}.title{font-size:30px;margin:5px 0 0}.button{display:inline-flex;align-items:center;text-decoration:none;color:#F8FAFC;border:1px solid #26304A;padding:10px 14px;border-radius:10px;font-weight:650;font-size:13px}.layout{display:grid;grid-template-columns:260px 1fr;gap:18px}.panel{background:#151B2E;border:1px solid #26304A;border-radius:16px;padding:18px}.nav{display:grid;gap:8px}.nav a,.nav span{padding:10px 12px;border-radius:9px;color:#CBD5E1;text-decoration:none}.nav .active{background:#202942;color:#fff}.account{margin-top:20px;padding-top:16px;border-top:1px solid #26304A}.account-name{font-weight:700}.muted{color:#94A3B8;font-size:13px}.status{display:inline-flex;margin-top:8px;padding:5px 8px;border-radius:999px;border:1px solid #475569;font-size:11px;text-transform:uppercase}.mailbox{display:grid;grid-template-columns:minmax(320px,42%) 1fr;min-height:620px}.messages{border-right:1px solid #26304A;padding-right:18px}.message-list{display:grid;gap:10px;margin-top:16px}.message{padding:14px;border:1px solid #26304A;border-radius:12px;background:#11182A}.message strong{display:block;margin-bottom:5px}.reader{padding-left:18px}.empty{height:100%;display:grid;place-items:center;text-align:center;color:#94A3B8;padding:32px}.notice{padding:14px;border:1px solid #854D0E;background:#422006;border-radius:12px;color:#FDE68A;margin-bottom:18px}.facts{display:grid;gap:8px;margin-top:16px}.fact{display:flex;justify-content:space-between;gap:16px;font-size:13px;border-top:1px solid #26304A;padding-top:8px}.fact span:first-child{color:#94A3B8}.fact span:last-child{text-align:right;word-break:break-word}@media(max-width:900px){.layout{grid-template-columns:1fr}.mailbox{grid-template-columns:1fr}.messages{border-right:0;padding-right:0}.reader{padding-left:0;margin-top:18px}} 
    </style>
</head>
<body>
<div class="shell">
    <header class="topbar">
        <div>
            <div class="eyebrow">Cockpit · Comunicação</div>
            <h1 class="title">Webmail Vitrine IA Pro</h1>
        </div>
        <a class="button" href="{{ route('cockpit.index') }}">Voltar ao Cockpit</a>
    </header>

    @if(!$enabled)
        <div class="notice">Módulo criado e protegido no Cockpit. A conexão IMAP/SMTP ainda aguarda o cadastro seguro das contas e credenciais.</div>
    @endif

    <div class="layout">
        <aside class="panel">
            <nav class="nav">
                <span class="active">Caixa de entrada</span>
                <span>Enviados</span>
                <span>Rascunhos</span>
                <span>Spam</span>
                <span>Lixeira</span>
            </nav>

            @foreach($accounts as $key => $account)
                <section class="account">
                    <div class="account-name">{{ $account['label'] ?? $key }}</div>
                    <div class="muted">{{ $account['address'] ?: 'E-mail ainda não configurado' }}</div>
                    <span class="status">{{ $enabled && !empty($account['address']) ? 'Configurado' : 'Pendente' }}</span>
                    <div class="facts">
                        <div class="fact"><span>Provedor</span><span>{{ $account['provider'] ?: 'Não informado' }}</span></div>
                        <div class="fact"><span>IMAP</span><span>{{ $account['imap_host'] ?: 'Pendente' }}</span></div>
                        <div class="fact"><span>SMTP</span><span>{{ $account['smtp_host'] ?: 'Pendente' }}</span></div>
                    </div>
                </section>
            @endforeach
        </aside>

        <main class="panel mailbox">
            <section class="messages">
                <div class="eyebrow">Mensagens</div>
                <div class="message-list">
                    <article class="message">
                        <strong>Caixa pronta para integração</strong>
                        <div class="muted">Assim que IMAP/SMTP forem configurados, as mensagens aparecerão aqui.</div>
                    </article>
                </div>
            </section>
            <section class="reader">
                <div class="empty">
                    <div>
                        <strong>Central de e-mails do ecossistema</strong>
                        <p>Leitura, resposta, encaminhamento, anexos e busca serão habilitados após a conexão segura das contas.</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
</body>
</html>
