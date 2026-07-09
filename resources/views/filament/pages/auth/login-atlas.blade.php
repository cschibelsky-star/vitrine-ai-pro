@php
    $passwordResetUrl = url('/admin/password-reset/request');
@endphp

<div class="vai-login-page">
    <main class="vai-login-shell">
        <section class="vai-login-left">
            <div class="vai-brand">
                <div class="vai-brand-mark"><div class="vai-mark-v">VI</div></div>
                <div class="vai-brand-word"><strong>Vitrine</strong><span>AI Pro</span></div>
            </div>

            <div class="vai-badge">Centro Operacional</div>
            <h1>Vitrine IA Pro Enterprise</h1>
            <p>Plataforma completa para gestão de clientes, licenças, produtos, Factory, IA Center, financeiro e operação do ecossistema.</p>

            <div class="vai-orbit">
                <div class="vai-ring"></div>
                <div class="vai-core"><div class="vai-mark-v">VI</div></div>
                <div class="vai-node vai-n1"><div class="vai-circle">⚙</div>Factory</div>
                <div class="vai-node vai-n2"><div class="vai-circle">▮▮▮</div>Analytics</div>
                <div class="vai-node vai-n3"><div class="vai-circle">🤖</div>IA Center</div>
                <div class="vai-node vai-n4"><div class="vai-circle">$</div>Financeiro</div>
                <div class="vai-node vai-n5"><div class="vai-circle">●●</div>Clientes</div>
            </div>

            <div class="vai-help">
                <div>
                    <h2>Precisa de ajuda?</h2>
                    <p>Nossa equipe está pronta para te ajudar sempre que precisar.</p>
                </div>
                <div class="vai-bot">🤖</div>
                <div class="vai-support">
                    <a href="#"><span>☘</span><div><b>WhatsApp</b><small>Fale conosco</small></div></a>
                    <a href="#"><span>🎧</span><div><b>Suporte</b><small>Abrir chamado</small></div></a>
                    <a href="#"><span>🤖</span><div><b>Assistente IA</b><small>● Online agora</small></div></a>
                </div>
            </div>
        </section>

        <section class="vai-login-card">
            <div class="vai-shield">🛡</div>
            <h2>Acessar Plataforma</h2>
            <p>Entre com suas credenciais para continuar</p>

            <form wire:submit="authenticate" class="vai-form">
                {{ $this->form }}

                <div class="vai-login-links">
                    <span>Ambiente protegido</span>
                    <a href="{{ $passwordResetUrl }}">Esqueceu sua senha?</a>
                </div>

                <button class="vai-login-button" type="submit">Entrar</button>
            </form>

            <div class="vai-terms">Ao entrar, você concorda com nossos<br><a href="#">Termos de Uso</a> e <a href="#">Política de Privacidade.</a></div>
        </section>
    </main>

    <footer class="vai-login-footer">
        <span>© 2026 Vitrine IA Pro. Todos os direitos reservados.</span>
        <div><span>Segurança Garantida</span><span>Dados Protegidos</span><span>Ambiente 100% Seguro</span></div>
        <span>Versão: 10.1 Enterprise</span>
    </footer>

    <style>
        .fi-simple-layout, .fi-simple-main, .fi-simple-page { max-width: none !important; padding: 0 !important; margin: 0 !important; background: transparent !important; }
        .fi-logo, .fi-simple-header { display: none !important; }
        body { background: #020817 !important; }
        .vai-login-page{min-height:100vh;background:radial-gradient(circle at 12% 10%,rgba(0,102,255,.28),transparent 34rem),radial-gradient(circle at 96% 12%,rgba(37,99,235,.35),transparent 36rem),linear-gradient(135deg,#010716 0%,#061844 48%,#03102a 100%);color:#f8fafc;font-family:Inter,Segoe UI,Arial,sans-serif;display:grid;grid-template-rows:1fr auto;padding:34px 36px 0;overflow-x:hidden}.vai-login-shell{display:grid;grid-template-columns:1fr .96fr;gap:54px;align-items:center;max-width:1480px;width:100%;margin:0 auto}.vai-login-left{min-height:760px;position:relative;display:flex;flex-direction:column}.vai-brand{display:flex;align-items:center;gap:18px;margin-bottom:34px}.vai-brand-mark{width:100px;height:100px;border:1px solid rgba(56,189,248,.42);border-radius:12px;background:linear-gradient(145deg,rgba(4,13,33,.98),rgba(10,28,74,.86));display:grid;place-items:center;box-shadow:0 0 35px rgba(56,189,248,.24);position:relative}.vai-brand-mark:before{content:"";position:absolute;left:-34px;top:28px;width:46px;height:2px;background:#38bdf8;box-shadow:-12px -14px 0 -1px #38bdf8,-24px 12px 0 -1px #38bdf8}.vai-mark-v{font-weight:1000;font-size:56px;line-height:1;background:linear-gradient(135deg,#0b4cc9 0%,#1b64ff 42%,#8cf7ff 44%,#e9ffff 100%);-webkit-background-clip:text;color:transparent;letter-spacing:-.16em;transform:skew(-8deg)}.vai-brand-word strong{display:block;font-size:39px;letter-spacing:.06em;line-height:.9;text-transform:uppercase}.vai-brand-word span{display:flex;align-items:center;gap:14px;color:#6ee7ff;text-transform:uppercase;letter-spacing:.28em;font-size:21px;font-weight:800;margin-top:10px}.vai-brand-word span:before,.vai-brand-word span:after{content:"";display:block;width:54px;height:1px;background:#6ee7ff}.vai-badge{display:inline-flex;border:1px solid rgba(37,99,235,.72);background:rgba(37,99,235,.16);color:#38a7ff;border-radius:8px;padding:7px 12px;font-weight:800;font-size:13px;text-transform:uppercase;letter-spacing:.04em;width:max-content}.vai-login-left h1{font-size:48px;line-height:1.04;margin:14px 0;color:#fff;letter-spacing:-.04em}.vai-login-left p{font-size:23px;line-height:1.42;color:#e5e7eb;max-width:660px;margin:0}.vai-orbit{height:350px;position:relative;margin-top:24px;margin-bottom:22px;flex-shrink:0}.vai-core{position:absolute;left:50%;top:50%;width:150px;height:150px;border:1px solid rgba(56,189,248,.36);border-radius:18px;background:linear-gradient(145deg,rgba(3,7,18,.96),rgba(12,31,73,.9));transform:translate(-50%,-50%);display:grid;place-items:center;box-shadow:0 0 55px rgba(37,99,235,.45)}.vai-core .vai-mark-v{font-size:86px}.vai-ring{position:absolute;left:50%;top:50%;width:300px;height:230px;border:1px solid rgba(8,102,255,.7);border-radius:50%;transform:translate(-50%,-50%);box-shadow:0 0 35px rgba(8,102,255,.22)}.vai-node{position:absolute;text-align:center;color:#fff;font-weight:900;font-size:13px;text-transform:uppercase}.vai-circle{width:72px;height:72px;border-radius:50%;display:grid;place-items:center;margin:auto auto 8px;border:2px solid rgba(0,102,255,.9);background:radial-gradient(circle,#2ca9ff,#0f4ed8 68%,#072256);box-shadow:0 0 24px rgba(8,102,255,.45);font-size:30px}.vai-n1{left:50%;top:0;transform:translateX(-50%)}.vai-n2{right:82px;top:82px}.vai-n3{right:110px;bottom:28px}.vai-n4{left:98px;bottom:28px}.vai-n4 .vai-circle{background:radial-gradient(circle,#17c964,#096b3f 70%);border-color:#10b981}.vai-n5{left:64px;top:82px}.vai-help{position:relative;border:1px solid rgba(59,130,246,.34);border-radius:16px;background:rgba(2,8,23,.56);padding:22px 26px;display:grid;grid-template-columns:1fr 96px;gap:18px;align-items:end;margin-top:auto;z-index:2}.vai-help h2{font-size:23px;margin:0 0 8px}.vai-help p{font-size:18px;line-height:1.35;color:#e5e7eb;margin:0;max-width:450px}.vai-support{grid-column:1/-1;display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:4px}.vai-support a{border:1px solid rgba(59,130,246,.28);border-radius:10px;background:rgba(8,38,99,.52);padding:14px 18px;color:#fff;text-decoration:none;display:flex;gap:12px;align-items:center}.vai-support a:first-child{background:rgba(0,141,88,.38)}.vai-support b{display:block;font-size:18px}.vai-support small{display:block;color:#cbd5e1}.vai-bot{width:96px;height:96px;border-radius:50%;background:radial-gradient(circle,#eaf6ff,#86c5ff 62%,#0a4ed8);display:grid;place-items:center;font-size:48px;box-shadow:0 0 34px rgba(56,189,248,.35)}.vai-login-card{min-height:820px;border:1px solid rgba(59,130,246,.34);border-radius:20px;background:linear-gradient(180deg,rgba(2,8,23,.82),rgba(3,12,32,.92));padding:58px;display:flex;flex-direction:column;justify-content:center;box-shadow:0 25px 80px rgba(0,0,0,.32)}.vai-shield{width:92px;height:92px;border-radius:50%;border:1px solid rgba(8,102,255,.58);background:rgba(8,102,255,.10);display:grid;place-items:center;margin:0 auto 30px;color:#0b6cff;font-size:42px}.vai-login-card h2{text-align:center;font-size:40px;margin:0 0 14px;color:#fff}.vai-login-card p{text-align:center;font-size:20px;color:#d1d5db;margin:0 0 34px}.vai-form .fi-input-wrp{background:rgba(2,8,23,.58)!important;border:1px solid rgba(148,163,184,.35)!important;border-radius:8px!important;min-height:64px!important}.vai-form .fi-input{color:#fff!important;font-size:18px!important}.vai-form .fi-fo-field-wrp-label span{color:#e5e7eb!important;font-size:14px!important}.vai-login-links{display:flex;justify-content:space-between;align-items:center;margin:20px 0 22px;color:#e5e7eb;font-size:15px}.vai-login-links a{color:#0b72ff;text-decoration:none;font-weight:800}.vai-login-button{height:68px;border-radius:8px;border:0;background:#075ff2;color:#fff;font-size:22px;font-weight:900;width:100%;box-shadow:0 18px 44px rgba(7,95,242,.25);cursor:pointer}.vai-terms{text-align:center;color:#cbd5e1;font-size:15px;line-height:1.45;margin-top:32px}.vai-terms a{color:#0b72ff;text-decoration:none}.vai-login-footer{height:76px;border-top:1px solid rgba(59,130,246,.22);display:flex;align-items:center;justify-content:space-between;color:#cbd5e1;font-size:15px;max-width:1480px;width:100%;margin:18px auto 0}.vai-login-footer div{display:flex;gap:70px}.vai-login-footer div span:before{content:"▱";color:#e5e7eb;margin-right:12px}@media(max-width:1100px){.vai-login-shell{grid-template-columns:1fr}.vai-login-left{min-height:auto}.vai-login-card{min-height:auto}.vai-login-footer,.vai-login-footer div{display:block;text-align:center}.vai-login-footer div{margin:12px 0}.vai-login-footer div span{display:block;margin:8px 0}}@media(max-width:720px){.vai-login-page{padding:18px}.vai-brand-word strong{font-size:30px}.vai-brand-word span{font-size:16px}.vai-login-left h1{font-size:36px}.vai-login-left p{font-size:18px}.vai-orbit{transform:scale(.82);transform-origin:top center;height:300px;margin-bottom:0}.vai-help{position:relative;margin-top:10px;grid-template-columns:1fr}.vai-support{grid-template-columns:1fr}.vai-login-card{padding:28px}.vai-login-card h2{font-size:30px}.vai-login-links{display:block}.vai-login-links a{display:block;margin-top:12px}}
    </style>
</div>
