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
                <div class="vai-node vai-n1"><div class="vai-circle">⚙</div>Factory</div>
                <div class="vai-node vai-n2"><div class="vai-circle">▮▮▮</div>Analytics</div>
                <div class="vai-node vai-n3"><div class="vai-circle">🤖</div>IA Center</div>
                <div class="vai-node vai-n4"><div class="vai-circle">$</div>Financeiro</div>
                <div class="vai-node vai-n5"><div class="vai-circle">●●</div>Clientes</div>
            </div>
        </section>

        <section class="vai-login-card">
            <div class="vai-shield">🛡</div>
            <h2>Acessar Plataforma</h2>
            <p>Entre com suas credenciais para continuar</p>

            <form wire:submit="authenticate" class="vai-form">
                <label class="vai-field">
                    <span>E-mail</span>
                    <input wire:model="data.email" type="email" autocomplete="email" placeholder="Digite seu e-mail" required>
                </label>

                <label class="vai-field">
                    <span>Senha</span>
                    <input wire:model="data.password" type="password" autocomplete="current-password" placeholder="Digite sua senha" required>
                </label>

                <div class="vai-options">
                    <label class="vai-remember"><input wire:model="data.remember" type="checkbox"> Lembrar de mim</label>
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
        html,body{margin:0!important;background:#020817!important;overflow:hidden!important}.fi-simple-layout,.fi-simple-main,.fi-simple-page{max-width:none!important;width:100vw!important;min-width:100vw!important;padding:0!important;margin:0!important;background:transparent!important}.fi-simple-header,.fi-logo{display:none!important}.fi-simple-main{display:block!important}.vai-login-page{position:fixed!important;inset:0!important;z-index:999999!important;overflow:auto!important;min-height:100vh;background:radial-gradient(circle at 12% 10%,rgba(0,102,255,.28),transparent 34rem),radial-gradient(circle at 96% 12%,rgba(37,99,235,.35),transparent 36rem),linear-gradient(135deg,#010716 0%,#061844 48%,#03102a 100%);color:#f8fafc;font-family:Inter,Segoe UI,Arial,sans-serif;display:grid;grid-template-rows:minmax(0,1fr) auto;padding:24px 30px 0}.vai-login-shell{display:grid;grid-template-columns:minmax(0,1fr) minmax(390px,.78fr);gap:34px;align-items:center;max-width:1320px;width:100%;margin:0 auto;min-height:calc(100vh - 82px)}.vai-login-left{min-height:0;position:relative;display:flex;flex-direction:column;justify-content:center}.vai-brand{display:flex;align-items:center;gap:16px;margin-bottom:22px}.vai-brand-mark{width:76px;height:76px;border:1px solid rgba(56,189,248,.42);border-radius:12px;background:linear-gradient(145deg,rgba(4,13,33,.98),rgba(10,28,74,.86));display:grid;place-items:center;box-shadow:0 0 35px rgba(56,189,248,.24);position:relative}.vai-brand-mark:before{content:"";position:absolute;left:-24px;top:22px;width:34px;height:2px;background:#38bdf8;box-shadow:-9px -11px 0 -1px #38bdf8,-18px 10px 0 -1px #38bdf8}.vai-mark-v{font-weight:1000;font-size:42px;line-height:1;background:linear-gradient(135deg,#0b4cc9 0%,#1b64ff 42%,#8cf7ff 44%,#e9ffff 100%);-webkit-background-clip:text;color:transparent;letter-spacing:-.16em;transform:skew(-8deg)}.vai-brand-word strong{display:block;font-size:31px;letter-spacing:.06em;line-height:.9;text-transform:uppercase}.vai-brand-word span{display:flex;align-items:center;gap:10px;color:#6ee7ff;text-transform:uppercase;letter-spacing:.24em;font-size:16px;font-weight:800;margin-top:8px}.vai-brand-word span:before,.vai-brand-word span:after{content:"";display:block;width:38px;height:1px;background:#6ee7ff}.vai-badge{display:inline-flex;border:1px solid rgba(37,99,235,.72);background:rgba(37,99,235,.16);color:#38a7ff;border-radius:8px;padding:6px 10px;font-weight:800;font-size:12px;text-transform:uppercase;letter-spacing:.04em;width:max-content}.vai-login-left h1{font-size:40px;line-height:1.02;margin:12px 0;color:#fff;letter-spacing:-.04em}.vai-login-left p{font-size:18px;line-height:1.38;color:#e5e7eb;max-width:620px;margin:0}.vai-orbit{height:300px;position:relative;margin-top:24px;flex-shrink:0;transform:scale(.92);transform-origin:center top}.vai-core{position:absolute;left:50%;top:50%;width:132px;height:132px;border:1px solid rgba(56,189,248,.36);border-radius:18px;background:linear-gradient(145deg,rgba(3,7,18,.96),rgba(12,31,73,.9));transform:translate(-50%,-50%);display:grid;place-items:center;box-shadow:0 0 55px rgba(37,99,235,.45)}.vai-core .vai-mark-v{font-size:74px}.vai-ring{position:absolute;left:50%;top:50%;width:280px;height:210px;border:1px solid rgba(8,102,255,.7);border-radius:50%;transform:translate(-50%,-50%);box-shadow:0 0 35px rgba(8,102,255,.22)}.vai-node{position:absolute;text-align:center;color:#fff;font-weight:900;font-size:12px;text-transform:uppercase}.vai-circle{width:62px;height:62px;border-radius:50%;display:grid;place-items:center;margin:auto auto 7px;border:2px solid rgba(0,102,255,.9);background:radial-gradient(circle,#2ca9ff,#0f4ed8 68%,#072256);box-shadow:0 0 24px rgba(8,102,255,.45);font-size:25px}.vai-n1{left:50%;top:0;transform:translateX(-50%)}.vai-n2{right:92px;top:78px}.vai-n3{right:120px;bottom:24px}.vai-n4{left:108px;bottom:24px}.vai-n4 .vai-circle{background:radial-gradient(circle,#17c964,#096b3f 70%);border-color:#10b981}.vai-n5{left:74px;top:78px}.vai-login-card{width:100%;max-width:520px;justify-self:end;min-height:auto;border:1px solid rgba(59,130,246,.34);border-radius:20px;background:linear-gradient(180deg,rgba(2,8,23,.82),rgba(3,12,32,.92));padding:36px 42px;display:flex;flex-direction:column;justify-content:center;box-shadow:0 25px 80px rgba(0,0,0,.32)}.vai-shield{width:72px;height:72px;border-radius:50%;border:1px solid rgba(8,102,255,.58);background:rgba(8,102,255,.10);display:grid;place-items:center;margin:0 auto 20px;color:#0b6cff;font-size:34px}.vai-login-card h2{text-align:center;font-size:32px;margin:0 0 10px;color:#fff}.vai-login-card p{text-align:center;font-size:16px;color:#d1d5db;margin:0 0 24px}.vai-field{display:block;margin-bottom:14px}.vai-field span{display:block;color:#e5e7eb;font-size:13px;font-weight:800;margin-bottom:7px}.vai-field input{height:54px;width:100%;border:1px solid rgba(148,163,184,.35);border-radius:8px;background:rgba(2,8,23,.58);color:#fff;font-size:16px;padding:0 16px;outline:none}.vai-field input:focus{border-color:#0b72ff;box-shadow:0 0 0 4px rgba(11,114,255,.15)}.vai-options{display:flex;justify-content:space-between;align-items:center;margin:2px 0 18px;color:#e5e7eb;font-size:14px}.vai-options a{color:#0b72ff;text-decoration:none;font-weight:800}.vai-remember{display:flex;align-items:center;gap:9px}.vai-remember input{width:18px;height:18px;accent-color:#075ff2}.vai-login-button{height:58px;border-radius:8px;border:0;background:#075ff2;color:#fff;font-size:19px;font-weight:900;width:100%;box-shadow:0 18px 44px rgba(7,95,242,.25);cursor:pointer}.vai-terms{text-align:center;color:#cbd5e1;font-size:13px;line-height:1.45;margin-top:24px}.vai-terms a{color:#0b72ff;text-decoration:none}.vai-login-footer{min-height:58px;border-top:1px solid rgba(59,130,246,.22);display:flex;align-items:center;justify-content:space-between;color:#cbd5e1;font-size:13px;max-width:1320px;width:100%;margin:0 auto}.vai-login-footer div{display:flex;gap:42px}.vai-login-footer div span:before{content:"▱";color:#e5e7eb;margin-right:10px}@media(max-height:760px){.vai-login-page{padding-top:16px}.vai-brand{margin-bottom:14px}.vai-login-left h1{font-size:34px}.vai-login-left p{font-size:16px}.vai-orbit{height:250px;transform:scale(.78)}.vai-login-card{padding:28px 34px}.vai-shield{width:58px;height:58px;font-size:28px;margin-bottom:14px}.vai-login-card h2{font-size:28px}.vai-login-card p{margin-bottom:18px}.vai-field input{height:48px}.vai-login-button{height:52px}.vai-terms{margin-top:16px}.vai-login-footer{min-height:46px;font-size:12px}}@media(max-width:1100px){html,body{overflow:auto!important}.vai-login-page{position:relative!important;min-height:100vh;overflow:auto!important}.vai-login-shell{grid-template-columns:1fr;min-height:auto}.vai-login-left{min-height:auto}.vai-login-card{max-width:100%;justify-self:stretch}.vai-login-footer,.vai-login-footer div{display:block;text-align:center}.vai-login-footer{padding:14px 0}.vai-login-footer div{margin:8px 0}.vai-login-footer div span{display:block;margin:6px 0}}@media(max-width:720px){.vai-login-page{padding:18px}.vai-brand-word strong{font-size:28px}.vai-brand-word span{font-size:15px}.vai-login-left h1{font-size:32px}.vai-login-left p{font-size:16px}.vai-orbit{transform:scale(.78);transform-origin:top center;height:260px}.vai-login-card{padding:24px}.vai-login-card h2{font-size:28px}.vai-options{display:block}.vai-options a{display:block;margin-top:12px}}
    </style>
</div>
