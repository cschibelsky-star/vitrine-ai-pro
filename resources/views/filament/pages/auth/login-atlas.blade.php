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
            <p>IA, SaaS, Factory, clientes, produtos, licenças e operação do ecossistema conectados em uma única plataforma.</p>

            <div class="vai-orbit">
                <div class="vai-ring"></div>
                <div class="vai-core"><div class="vai-mark-v">VI</div></div>
                <div class="vai-node vai-n1"><div class="vai-circle">⚙</div><span>Factory</span></div>
                <div class="vai-node vai-n2"><div class="vai-circle">▮▮▮</div><span>Analytics</span></div>
                <div class="vai-node vai-n3"><div class="vai-circle">🤖</div><span>IA Center</span></div>
                <div class="vai-node vai-n4"><div class="vai-circle">$</div><span>Financeiro</span></div>
                <div class="vai-node vai-n5"><div class="vai-circle">●●</div><span>Clientes</span></div>
            </div>
        </section>

        <section class="vai-login-card">
            <div class="vai-shield">🛡</div>
            <h2>Acessar Plataforma</h2>
            <p>Entre com suas credenciais para continuar</p>

            <form wire:submit="authenticate" class="vai-form">
                {{ $this->form }}

                <div class="vai-login-links">
                    <span></span>
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
        .fi-simple-layout,.fi-simple-main,.fi-simple-page{max-width:none!important;width:100%!important;padding:0!important;margin:0!important;background:transparent!important}.fi-logo,.fi-simple-header{display:none!important}body{background:#020817!important}.vai-login-page{min-height:100vh;background:radial-gradient(circle at 12% 10%,rgba(0,102,255,.26),transparent 32rem),radial-gradient(circle at 96% 12%,rgba(37,99,235,.32),transparent 34rem),linear-gradient(135deg,#010716 0%,#061844 48%,#03102a 100%);color:#f8fafc;font-family:Inter,Segoe UI,Arial,sans-serif;display:grid;grid-template-rows:1fr auto;padding:22px 34px 0;overflow:hidden}.vai-login-shell{display:grid;grid-template-columns:1fr .96fr;gap:44px;align-items:center;max-width:1460px;width:100%;margin:0 auto}.vai-login-left{min-height:650px;position:relative;display:flex;flex-direction:column;justify-content:center}.vai-brand{display:flex;align-items:center;gap:15px;margin-bottom:24px}.vai-brand-mark{width:78px;height:78px;border:1px solid rgba(56,189,248,.42);border-radius:12px;background:linear-gradient(145deg,rgba(4,13,33,.98),rgba(10,28,74,.86));display:grid;place-items:center;box-shadow:0 0 35px rgba(56,189,248,.24);position:relative}.vai-brand-mark:before{content:"";position:absolute;left:-28px;top:24px;width:38px;height:2px;background:#38bdf8;box-shadow:-10px -12px 0 -1px #38bdf8,-20px 10px 0 -1px #38bdf8}.vai-mark-v{font-weight:1000;font-size:42px;line-height:1;background:linear-gradient(135deg,#0b4cc9 0%,#1b64ff 42%,#8cf7ff 44%,#e9ffff 100%);-webkit-background-clip:text;color:transparent;letter-spacing:-.16em;transform:skew(-8deg)}.vai-brand-word strong{display:block;font-size:33px;letter-spacing:.06em;line-height:.9;text-transform:uppercase}.vai-brand-word span{display:flex;align-items:center;gap:12px;color:#6ee7ff;text-transform:uppercase;letter-spacing:.28em;font-size:18px;font-weight:800;margin-top:8px}.vai-brand-word span:before,.vai-brand-word span:after{content:"";display:block;width:48px;height:1px;background:#6ee7ff}.vai-badge{display:inline-flex;border:1px solid rgba(37,99,235,.72);background:rgba(37,99,235,.16);color:#38a7ff;border-radius:8px;padding:6px 11px;font-weight:800;font-size:12px;text-transform:uppercase;letter-spacing:.04em;width:max-content}.vai-login-left h1{font-size:38px;line-height:1.04;margin:14px 0 10px;color:#fff;letter-spacing:-.04em}.vai-login-left p{font-size:17px;line-height:1.45;color:#e5e7eb;max-width:650px;margin:0}.vai-orbit{height:320px;position:relative;margin-top:30px;flex-shrink:0}.vai-core{position:absolute;left:50%;top:50%;width:128px;height:128px;border:1px solid rgba(56,189,248,.36);border-radius:18px;background:linear-gradient(145deg,rgba(3,7,18,.96),rgba(12,31,73,.9));transform:translate(-50%,-50%);display:grid;place-items:center;box-shadow:0 0 55px rgba(37,99,235,.45)}.vai-core .vai-mark-v{font-size:70px}.vai-ring{position:absolute;left:50%;top:50%;width:315px;height:215px;border:1px solid rgba(8,102,255,.7);border-radius:50%;transform:translate(-50%,-50%);box-shadow:0 0 35px rgba(8,102,255,.22)}.vai-node{position:absolute;text-align:center;color:#fff;font-weight:900;font-size:11px;text-transform:uppercase;min-width:82px}.vai-node span{display:block;margin-top:6px}.vai-circle{width:62px;height:62px;border-radius:50%;display:grid;place-items:center;margin:auto;border:2px solid rgba(0,102,255,.9);background:radial-gradient(circle,#2ca9ff,#0f4ed8 68%,#072256);box-shadow:0 0 24px rgba(8,102,255,.45);font-size:22px}.vai-n1{left:50%;top:0;transform:translateX(-50%)}.vai-n2{right:78px;top:82px}.vai-n3{right:96px;bottom:22px}.vai-n4{left:96px;bottom:22px}.vai-n4 .vai-circle{background:radial-gradient(circle,#17c964,#096b3f 70%);border-color:#10b981}.vai-n5{left:68px;top:82px}.vai-login-card{min-height:650px;border:1px solid rgba(59,130,246,.34);border-radius:20px;background:linear-gradient(180deg,rgba(2,8,23,.82),rgba(3,12,32,.92));padding:42px;display:flex;flex-direction:column;justify-content:center;box-shadow:0 25px 80px rgba(0,0,0,.32)}.vai-shield{width:74px;height:74px;border-radius:50%;border:1px solid rgba(8,102,255,.58);background:rgba(8,102,255,.10);display:grid;place-items:center;margin:0 auto 24px;color:#0b6cff;font-size:34px}.vai-login-card h2{text-align:center;font-size:34px;margin:0 0 10px;color:#fff}.vai-login-card p{text-align:center;font-size:17px;color:#d1d5db;margin:0 0 28px}.vai-form label,.vai-form .fi-fo-field-wrp-label,.vai-form .fi-fo-field-wrp-label span{display:none!important}.vai-form .fi-fo-field-wrp{margin:0 0 18px!important}.vai-form .fi-input-wrp{height:62px!important;min-height:62px!important;background:rgba(2,8,23,.58)!important;border:1px solid rgba(148,163,184,.35)!important;border-radius:8px!important;box-shadow:none!important;outline:none!important;overflow:hidden!important}.vai-form .fi-input-wrp:focus-within{border-color:#07c9f2!important;box-shadow:0 0 0 2px rgba(7,201,242,.22)!important}.vai-form input:not([type=checkbox]){height:60px!important;width:100%!important;background:transparent!important;border:0!important;outline:0!important;box-shadow:none!important;color:#fff!important;font-size:18px!important;padding:0 18px!important}.vai-form input:not([type=checkbox])::placeholder{color:#aeb9c9!important;opacity:1!important}.vai-form input:-webkit-autofill{-webkit-text-fill-color:#fff!important;transition:background-color 9999s ease-in-out 0s!important}.vai-form a:not(.vai-login-links a){display:none!important}.vai-form .fi-input-wrp-prefix,.vai-form .fi-input-wrp-suffix{background:transparent!important;border:0!important}.vai-form .fi-input-wrp-suffix button{width:44px!important;height:44px!important;overflow:hidden!important;color:transparent!important;font-size:0!important;background:transparent!important;border:0!important;box-shadow:none!important}.vai-form .fi-input-wrp-suffix button span{display:none!important}.vai-form .fi-input-wrp-suffix button svg{display:block!important;color:#cbd5e1!important;width:22px!important;height:22px!important}.vai-form .fi-checkbox-input,input[type=checkbox]{appearance:none!important;-webkit-appearance:none!important;width:20px!important;height:20px!important;border:1px solid #cbd5e1!important;border-radius:4px!important;background:transparent!important;box-shadow:none!important;margin:0!important}.vai-form .fi-checkbox-input:checked,input[type=checkbox]:checked{background:#07c9f2!important;border-color:#07c9f2!important}.vai-form .fi-checkbox-list-option-label,.vai-form .fi-checkbox-label{display:inline-flex!important;color:#e5e7eb!important;font-size:15px!important;font-weight:700!important}.vai-form .fi-checkbox-list-option,.vai-form .fi-fo-checkbox-list{display:flex!important;align-items:center!important;gap:10px!important;margin-top:-4px!important}.vai-login-links{display:flex;justify-content:flex-end;align-items:center;margin:-34px 0 24px;color:#e5e7eb;font-size:14px;position:relative;z-index:3}.vai-login-links a{color:#0b72ff;text-decoration:none;font-weight:800}.vai-login-button{height:60px;border-radius:8px;border:0;background:#07c9f2;color:#fff;font-size:22px;font-weight:900;width:100%;box-shadow:0 18px 44px rgba(7,95,242,.25);cursor:pointer}.vai-terms{text-align:center;color:#cbd5e1;font-size:14px;line-height:1.45;margin-top:25px}.vai-terms a{color:#0b72ff;text-decoration:none}.vai-login-footer{height:48px;border-top:1px solid rgba(59,130,246,.22);display:flex;align-items:center;justify-content:space-between;color:#cbd5e1;font-size:13px;max-width:1460px;width:100%;margin:12px auto 0}.vai-login-footer div{display:flex;gap:50px}.vai-login-footer div span:before{content:"▱";color:#e5e7eb;margin-right:10px}@media(max-width:1100px){.vai-login-page{overflow:auto}.vai-login-shell{grid-template-columns:1fr}.vai-login-left{min-height:auto}.vai-login-card{min-height:auto}.vai-login-footer,.vai-login-footer div{display:block;text-align:center}.vai-login-footer div{margin:12px 0}.vai-login-footer div span{display:block;margin:8px 0}}@media(max-width:720px){.vai-login-page{padding:18px;overflow:auto}.vai-brand-word strong{font-size:30px}.vai-brand-word span{font-size:16px}.vai-login-left h1{font-size:34px}.vai-login-left p{font-size:16px}.vai-orbit{transform:scale(.82);transform-origin:top center;height:250px;margin-bottom:0}.vai-login-card{padding:28px}.vai-login-card h2{font-size:30px}.vai-login-links{display:block;margin:0 0 20px}.vai-login-links a{display:block;margin-top:12px}}
    </style>
</div>
