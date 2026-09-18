<x-filament-panels::page>
    @php
        $agents = $this->getAgents();
        $runtime = $this->getRuntime();
        $pipeline = $this->getPipeline();
        $campaignState = $this->getCampaignState();
        $campaign = $campaignState['campaign'] ?? null;
        $tasks = $campaignState['tasks'] ?? [];
        $enabledAgents = collect($agents)->filter(fn (array $agent) => (bool) ($agent['enabled'] ?? false))->count();
        $activeCampaigns = $campaign ? 1 : 0;
        $taskCount = count($tasks);
    @endphp

    <style>
        :root {
            --vm-bg:#090716;
            --vm-bg-2:#0d0a20;
            --vm-side:#171032;
            --vm-card:#17142e;
            --vm-card-2:#1d1838;
            --vm-border:rgba(139,92,246,.18);
            --vm-border-strong:rgba(139,92,246,.38);
            --vm-purple:#8b5cf6;
            --vm-purple-2:#6d28d9;
            --vm-magenta:#d946ef;
            --vm-text:#f7f5ff;
            --vm-muted:#9f9ab9;
            --vm-green:#34d399;
            --vm-yellow:#fbbf24;
            --vm-blue:#60a5fa;
        }

        .fi-sidebar,.fi-topbar { display:none!important; }
        .fi-main-ctn { margin-left:0!important; }
        .fi-main { max-width:none!important; padding:0!important; }
        .fi-page-header { display:none!important; }
        .fi-body { background:var(--vm-bg)!important; }
        .fi-layout { background:var(--vm-bg)!important; min-height:100vh!important; }

        .vm-shell {
            min-height:100vh;
            background:
                radial-gradient(circle at 52% -18%,rgba(124,58,237,.16),transparent 34%),
                linear-gradient(180deg,#0a0718 0%,#090716 100%);
            color:var(--vm-text);
            font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
        }
        .vm-layout { display:grid; grid-template-columns:280px minmax(0,1fr); min-height:100vh; }

        .vm-sidebar {
            position:sticky; top:0; height:100vh; padding:22px 14px 18px;
            background:linear-gradient(180deg,#211044 0%,#17102f 44%,#0f0b22 100%);
            border-right:1px solid rgba(139,92,246,.18);
            box-shadow:14px 0 42px rgba(0,0,0,.2);
            display:flex; flex-direction:column; z-index:20;
        }
        .vm-logo { display:flex; align-items:center; gap:12px; padding:2px 10px 22px; }
        .vm-logo-mark {
            width:48px;height:48px;border-radius:15px 15px 22px 8px;
            background:linear-gradient(145deg,#c084fc 0%,#7c3aed 46%,#4338ca 100%);
            box-shadow:0 0 32px rgba(139,92,246,.5), inset 0 1px 1px rgba(255,255,255,.35);
            clip-path:polygon(0 0,100% 0,73% 100%,42% 58%);
        }
        .vm-logo-title { font-size:21px;font-weight:850;letter-spacing:-.04em;white-space:nowrap; }
        .vm-logo-title span { color:#b794f6; }
        .vm-logo-sub { margin-top:2px;font-size:10px;letter-spacing:.18em;color:#aaa3c7;text-transform:uppercase; }

        .vm-nav { display:grid; gap:8px; margin-top:5px; }
        .vm-nav a {
            display:flex;align-items:center;gap:13px;padding:12px 14px;border-radius:12px;
            color:#c9c3dc;text-decoration:none;font-size:14px;font-weight:560;
            transition:.18s ease;
        }
        .vm-nav a:hover { color:white;background:rgba(124,58,237,.18); }
        .vm-nav a.active {
            color:white;background:linear-gradient(90deg,#8b5cf6,#6d28d9);
            box-shadow:0 10px 28px rgba(124,58,237,.34);
        }
        .vm-nav svg { width:20px;height:20px;stroke-width:1.8; }

        .vm-side-card {
            margin-top:auto;padding:20px 18px;border-radius:14px;
            background:linear-gradient(155deg,rgba(27,20,62,.96),rgba(9,7,24,.96));
            border:1px solid rgba(139,92,246,.14);
        }
        .vm-side-spark { font-size:24px;color:#a855f7; }
        .vm-side-card strong { display:block;margin-top:10px;font-size:20px;line-height:1.18;letter-spacing:-.03em; }
        .vm-side-rule { width:42px;height:3px;border-radius:99px;background:linear-gradient(90deg,#a855f7,#7c3aed);margin:22px 0; }
        .vm-side-foot { color:#a29bbd;font-size:10px;line-height:1.5;text-transform:uppercase;letter-spacing:.12em; }

        .vm-main { min-width:0; }
        .vm-topbar {
            height:78px;padding:16px 26px;display:flex;align-items:center;gap:20px;
            border-bottom:1px solid rgba(139,92,246,.12);background:rgba(9,7,22,.82);backdrop-filter:blur(18px);
            position:sticky;top:0;z-index:15;
        }
        .vm-search {
            flex:1;max-width:720px;display:flex;align-items:center;gap:12px;padding:0 16px;height:44px;
            border-radius:14px;background:#17142e;border:1px solid rgba(139,92,246,.18);color:#77728f;
        }
        .vm-search span { font-size:13px; }
        .vm-key { margin-left:auto;padding:3px 8px;border-radius:7px;border:1px solid rgba(255,255,255,.08);font-size:11px;color:#9f9ab9; }
        .vm-top-actions { margin-left:auto;display:flex;align-items:center;gap:18px; }
        .vm-bell { position:relative;width:36px;height:36px;display:grid;place-items:center;color:#d8d3e8; }
        .vm-bell::after { content:"";position:absolute;right:6px;top:5px;width:7px;height:7px;border-radius:50%;background:#8b5cf6;box-shadow:0 0 10px #8b5cf6; }
        .vm-user { display:flex;align-items:center;gap:10px; }
        .vm-avatar { width:42px;height:42px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(145deg,#c4b5fd,#7c3aed);color:white;font-weight:800;font-size:13px;box-shadow:0 0 18px rgba(139,92,246,.25); }
        .vm-user-name { font-size:13px;font-weight:700; }
        .vm-user-role { font-size:11px;color:#918aa9; }

        .vm-content { padding:20px 26px 28px; }
        .vm-hero {
            position:relative;overflow:hidden;min-height:286px;border-radius:22px;padding:34px 36px;
            border:1px solid rgba(139,92,246,.36);
            background:
                radial-gradient(circle at 73% 18%,rgba(147,51,234,.52),transparent 21%),
                radial-gradient(circle at 57% 110%,rgba(124,58,237,.62),transparent 40%),
                linear-gradient(116deg,#220b54 0%,#160b39 42%,#090716 100%);
            box-shadow:0 20px 60px rgba(0,0,0,.26);
            display:grid;grid-template-columns:1.2fr .8fr;gap:30px;align-items:center;
        }
        .vm-hero::before {
            content:"";position:absolute;width:560px;height:560px;right:23%;top:-410px;border:2px solid rgba(255,255,255,.2);border-radius:50%;box-shadow:0 0 55px rgba(168,85,247,.46);
        }
        .vm-hero::after {
            content:"";position:absolute;width:300px;height:300px;right:-120px;top:-100px;border-radius:50%;background:rgba(124,58,237,.25);
        }
        .vm-hero-copy { position:relative;z-index:2; }
        .vm-eyebrow { font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:#c4b5fd;font-weight:700; }
        .vm-hero h1 { margin:14px 0 8px;font-size:58px;line-height:.98;letter-spacing:-.055em;font-weight:880;color:white; }
        .vm-gradient { background:linear-gradient(90deg,#8b5cf6,#d946ef);-webkit-background-clip:text;background-clip:text;color:transparent; }
        .vm-hero-lead { font-size:25px;font-weight:650;letter-spacing:-.025em;color:#f4f0ff; }
        .vm-hero-sub { margin-top:6px;font-size:14px;color:#aaa3bd; }
        .vm-cta {
            display:inline-flex;align-items:center;gap:12px;margin-top:26px;padding:12px 22px;border-radius:999px;
            color:white;text-decoration:none;font-size:13px;font-weight:750;background:linear-gradient(90deg,#6d28d9,#a855f7);
            box-shadow:0 12px 30px rgba(124,58,237,.34);border:1px solid rgba(196,181,253,.25);
        }
        .vm-hero-brand { position:relative;z-index:2;display:flex;align-items:center;justify-content:center;flex-direction:column;text-align:center; }
        .vm-big-mark { width:132px;height:122px;filter:drop-shadow(0 0 24px rgba(139,92,246,.62)); }
        .vm-big-brand { margin-top:6px;font-size:31px;font-weight:900;letter-spacing:-.045em; }
        .vm-big-brand span { color:#a855f7; }
        .vm-big-tag { margin-top:6px;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:#ded8ed; }

        .vm-stats { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:18px; }
        .vm-stat {
            padding:20px;border-radius:18px;background:linear-gradient(145deg,#1c1837,#141129);
            border:1px solid rgba(139,92,246,.1);box-shadow:0 14px 38px rgba(0,0,0,.18);
        }
        .vm-stat-head { display:flex;align-items:center;gap:13px; }
        .vm-stat-icon { width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:linear-gradient(145deg,#7c3aed,#5b21b6);box-shadow:0 9px 24px rgba(124,58,237,.25); }
        .vm-stat-label { font-size:12px;color:#aaa5bb; }
        .vm-stat-value { margin-top:13px;font-size:31px;font-weight:840;letter-spacing:-.035em; }
        .vm-stat-meta { margin-top:10px;font-size:11px;color:#77728d; }
        .vm-up { color:var(--vm-green);font-weight:700; }

        .vm-grid { display:grid;grid-template-columns:1.08fr .92fr;gap:16px;margin-top:16px; }
        .vm-panel { border-radius:18px;background:linear-gradient(145deg,#1a1732,#121026);border:1px solid rgba(139,92,246,.1);box-shadow:0 14px 38px rgba(0,0,0,.18);padding:20px; }
        .vm-panel-title { display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:16px; }
        .vm-panel-title h2 { margin:0;font-size:17px;color:white;font-weight:780;letter-spacing:-.02em; }
        .vm-link { color:#a855f7;font-size:12px;text-decoration:none; }
        .vm-campaigns { display:grid;gap:10px; }
        .vm-campaign-row { display:grid;grid-template-columns:56px 1fr auto auto;gap:12px;align-items:center;padding:8px 4px; }
        .vm-thumb { width:56px;height:44px;border-radius:9px;background:linear-gradient(145deg,#31205b,#151126);display:grid;place-items:center;color:#a78bfa;font-size:20px;overflow:hidden; }
        .vm-campaign-name { font-size:13px;font-weight:700;color:#f7f4ff; }
        .vm-campaign-type { margin-top:3px;font-size:11px;color:#827c95; }
        .vm-status { padding:5px 10px;border-radius:999px;font-size:10px;font-weight:700;white-space:nowrap; }
        .vm-status.green { background:rgba(16,185,129,.16);color:#6ee7b7; }
        .vm-status.yellow { background:rgba(245,158,11,.16);color:#fcd34d; }
        .vm-status.blue { background:rgba(59,130,246,.17);color:#93c5fd; }
        .vm-status.purple { background:rgba(168,85,247,.18);color:#d8b4fe; }
        .vm-time { font-size:10px;color:#6f6982;white-space:nowrap; }

        .vm-chart { height:270px;position:relative;overflow:hidden;border-radius:14px;background:linear-gradient(180deg,rgba(28,24,55,.46),rgba(13,11,31,.2)); }
        .vm-chart-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.04) 1px,transparent 1px);background-size:25% 25%; }
        .vm-chart svg { position:absolute;inset:16px 16px 28px;width:calc(100% - 32px);height:calc(100% - 44px); }
        .vm-empty-chart { position:absolute;inset:auto 0 10px;text-align:center;font-size:10px;color:#716b83; }
        .vm-legend { display:flex;justify-content:center;gap:22px;margin-top:12px;font-size:10px;color:#9b96ac; }
        .vm-dot { width:9px;height:9px;border-radius:50%;display:inline-block;margin-right:6px; }

        .vm-quote { margin-top:16px;padding:18px 22px;border-radius:16px;background:linear-gradient(90deg,#211347,#17112f 46%,#131027);border:1px solid rgba(139,92,246,.15);display:flex;align-items:center;justify-content:space-between;gap:20px; }
        .vm-quote-text { font-size:16px;color:#e8e4f3; }
        .vm-quote-brand { display:flex;align-items:center;gap:12px;color:#bdb5d3;font-size:11px;letter-spacing:.18em;text-transform:uppercase;white-space:nowrap; }
        .vm-quote-line { width:38px;height:3px;border-radius:99px;background:linear-gradient(90deg,#a855f7,#7c3aed); }

        .vm-copilot { margin-top:18px;scroll-margin-top:90px; }
        .vm-copilot-head { display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:14px; }
        .vm-copilot-head h2 { margin:0;font-size:18px;color:white; }
        .vm-secondary { padding:8px 12px;border-radius:10px;background:#1b1733;border:1px solid rgba(139,92,246,.22);color:#c9c2dd;font-size:11px; }
        .vm-chat { max-height:360px;min-height:160px;overflow-y:auto;border-radius:14px;background:#0e0b20;border:1px solid rgba(139,92,246,.12);padding:14px;display:grid;gap:10px; }
        .vm-msg { max-width:82%;padding:11px 13px;border-radius:12px;font-size:12px;line-height:1.5;white-space:pre-wrap; }
        .vm-msg.user { justify-self:end;background:linear-gradient(135deg,#7c3aed,#9333ea);color:white; }
        .vm-msg.ai { justify-self:start;background:#201a3d;color:#eee9fa;border:1px solid rgba(139,92,246,.14); }
        .vm-chat-empty { align-self:center;text-align:center;color:#79738d;font-size:12px;padding:35px; }
        .vm-form { display:grid;grid-template-columns:1fr auto;gap:10px;margin-top:12px; }
        .vm-form textarea { resize:vertical;min-height:66px;padding:12px 14px;border-radius:12px;background:#0f0c22!important;color:white!important;border:1px solid rgba(139,92,246,.24)!important;font-size:12px; }
        .vm-send { align-self:end;padding:12px 18px;border-radius:12px;background:linear-gradient(135deg,#6d28d9,#a855f7);color:white;font-weight:750;font-size:12px;border:0;box-shadow:0 10px 24px rgba(124,58,237,.28); }
        .vm-note { margin-top:10px;font-size:10px;color:#817b92;display:flex;gap:16px;flex-wrap:wrap; }
        .vm-error { margin-bottom:8px;color:#fda4af;font-size:11px; }

        .vm-workstation { margin-top:18px;scroll-margin-top:90px; }
        .vm-workstation-head { display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:16px; }
        .vm-workstation-head h2 { margin:4px 0 0;font-size:22px;color:white;letter-spacing:-.025em; }
        .vm-workstation-head p { margin:6px 0 0;max-width:720px;font-size:12px;line-height:1.55;color:#9b95ae; }
        .vm-version { padding:6px 10px;border-radius:999px;background:rgba(52,211,153,.1);border:1px solid rgba(52,211,153,.2);color:#6ee7b7;font-size:10px;font-weight:800;white-space:nowrap; }
        .vm-modules { display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-bottom:16px; }
        .vm-module { padding:14px;border-radius:14px;background:#100d25;border:1px solid rgba(139,92,246,.12);min-width:0; }
        .vm-module-top { display:flex;align-items:center;justify-content:space-between;gap:8px; }
        .vm-module-icon { width:34px;height:34px;border-radius:10px;display:grid;place-items:center;background:#21183d;color:#c4b5fd; }
        .vm-module-status { padding:4px 7px;border-radius:999px;font-size:9px;font-weight:800; }
        .vm-module-status.active { background:rgba(52,211,153,.12);color:#6ee7b7; }
        .vm-module-status.ready { background:rgba(96,165,250,.12);color:#93c5fd; }
        .vm-module-name { margin-top:10px;font-size:12px;font-weight:780;color:white; }
        .vm-module-desc { margin-top:4px;font-size:10px;line-height:1.45;color:#7f7992; }
        .vm-flow-layout { display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);gap:14px; }
        .vm-flow-box { border-radius:14px;background:#0e0b20;border:1px solid rgba(139,92,246,.12);padding:15px;min-width:0; }
        .vm-flow-title { display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px; }
        .vm-flow-title strong { font-size:13px;color:white; }
        .vm-flow-title span { font-size:9px;color:#8d87a0;text-transform:uppercase;letter-spacing:.12em; }
        .vm-flow-form { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
        .vm-field { min-width:0; }
        .vm-field.full { grid-column:1/-1; }
        .vm-field label { display:block;margin-bottom:5px;font-size:10px;color:#aaa4b9; }
        .vm-field input,.vm-field select,.vm-field textarea { width:100%;min-height:42px;border-radius:10px;background:#15112b!important;border:1px solid rgba(139,92,246,.2)!important;color:#f6f2ff!important;padding:9px 11px;font-size:12px;outline:none; }
        .vm-field textarea { min-height:76px;resize:vertical;line-height:1.45; }
        .vm-field input:focus,.vm-field select:focus,.vm-field textarea:focus { border-color:rgba(168,85,247,.68)!important;box-shadow:0 0 0 3px rgba(168,85,247,.1); }
        .vm-flow-actions { display:flex;gap:8px;flex-wrap:wrap;margin-top:12px; }
        .vm-flow-primary { padding:10px 15px;border-radius:10px;border:0;background:linear-gradient(135deg,#6d28d9,#a855f7);color:white;font-size:11px;font-weight:800; }
        .vm-flow-secondary { padding:10px 13px;border-radius:10px;background:#19142f;border:1px solid rgba(139,92,246,.18);color:#c9c3dc;font-size:11px;font-weight:700; }
        .vm-flow-output { min-height:338px;max-height:520px;overflow:auto;white-space:pre-wrap;padding:13px;border-radius:11px;background:#090718;border:1px solid rgba(139,92,246,.1);color:#ddd7ec;font-size:11px;line-height:1.55; }
        .vm-flow-empty { min-height:338px;display:grid;place-items:center;text-align:center;padding:26px;border-radius:11px;background:#090718;border:1px dashed rgba(139,92,246,.18);color:#746e87;font-size:11px;line-height:1.55; }
        .vm-bridge { margin-bottom:14px;padding:13px;border-radius:12px;background:#13102a;border:1px solid rgba(96,165,250,.14); }
        .vm-bridge-head { display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px; }
        .vm-bridge-head strong { color:white;font-size:12px; }
        .vm-bridge-state { padding:4px 8px;border-radius:999px;background:rgba(96,165,250,.1);color:#93c5fd;font-size:9px;font-weight:800; }
        .vm-flow-open { display:inline-flex;align-items:center;gap:6px;padding:10px 14px;border-radius:10px;background:#eef2ff;color:#312e81!important;font-size:11px;font-weight:850;text-decoration:none; }
        .vm-flow-open.disabled { pointer-events:none;opacity:.45; }
        .vm-job { margin-top:12px;padding:12px;border-radius:12px;background:#0b091b;border:1px solid rgba(52,211,153,.14); }
        .vm-job-head { display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap; }
        .vm-job-id { color:#f5f3ff;font-size:11px;font-weight:800;letter-spacing:.03em; }
        .vm-job-status { padding:5px 8px;border-radius:999px;background:rgba(52,211,153,.11);color:#6ee7b7;font-size:9px;font-weight:850; }
        .vm-job-meta { margin-top:7px;color:#817b92;font-size:10px;line-height:1.45; }
        .vm-job-actions { display:flex;gap:7px;flex-wrap:wrap;margin-top:10px; }
        .vm-job-actions button { padding:7px 9px;border-radius:9px;background:#19142f;border:1px solid rgba(139,92,246,.16);color:#c9c3dc;font-size:9px;font-weight:760; }
        .vm-operator { margin-top:12px; }
        .vm-operator pre { margin:8px 0 0;max-height:260px;overflow:auto;white-space:pre-wrap;padding:11px;border-radius:10px;background:#080615;border:1px solid rgba(139,92,246,.1);color:#bdb6cc;font-size:10px;line-height:1.5;font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }
        .vm-history { margin-top:12px;display:grid;gap:7px; }
        .vm-history-row { display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;padding:9px 10px;border-radius:10px;background:#100d24;border:1px solid rgba(139,92,246,.08); }
        .vm-history-row strong { display:block;color:#eee9fa;font-size:10px; }
        .vm-history-row span { color:#746e87;font-size:9px; }

        @media (max-width:1180px){
            .vm-layout{grid-template-columns:220px minmax(0,1fr)}
            .vm-logo-title{font-size:17px}.vm-hero h1{font-size:46px}.vm-stats{grid-template-columns:repeat(2,1fr)}
            .vm-grid{grid-template-columns:1fr}.vm-hero{grid-template-columns:1fr}.vm-hero-brand{display:none}.vm-modules{grid-template-columns:repeat(2,1fr)}.vm-flow-layout{grid-template-columns:1fr}
        }
        @media (max-width:760px){
            .vm-layout{display:block}.vm-sidebar{position:relative;height:auto;padding:14px}.vm-nav{grid-template-columns:repeat(2,1fr)}.vm-side-card{display:none}
            .vm-logo{padding-bottom:10px}.vm-main{width:100%}.vm-topbar{padding:10px 14px;height:64px}.vm-search{display:none}.vm-user-name,.vm-user-role{display:none}
            .vm-content{padding:14px}.vm-hero{min-height:auto;padding:25px 20px}.vm-hero h1{font-size:42px}.vm-hero-lead{font-size:20px}.vm-stats{grid-template-columns:1fr 1fr;gap:10px}.vm-stat{padding:15px}.vm-grid{gap:10px}.vm-campaign-row{grid-template-columns:48px 1fr auto}.vm-time{display:none}.vm-quote{align-items:flex-start;flex-direction:column}.vm-form{grid-template-columns:1fr}.vm-modules{grid-template-columns:1fr}.vm-flow-form{grid-template-columns:1fr}.vm-field.full{grid-column:auto}.vm-workstation-head{flex-direction:column}
        }
        @media (max-width:470px){ .vm-nav{grid-template-columns:1fr 1fr}.vm-stats{grid-template-columns:1fr}.vm-logo-title{font-size:16px}.vm-hero h1{font-size:36px}.vm-content{padding:10px}.vm-campaign-row{grid-template-columns:46px 1fr}.vm-status{display:none} }
    </style>

    <div class="vm-shell">
        <div class="vm-layout">
            <aside class="vm-sidebar">
                <div class="vm-logo">
                    <div class="vm-logo-mark" aria-hidden="true"></div>
                    <div>
                        <div class="vm-logo-title">VITRINE IA <span>PRO</span></div>
                        <div class="vm-logo-sub">Marketing IA</div>
                    </div>
                </div>

                <nav class="vm-nav" aria-label="Marketing IA">
                    <a class="active" href="#inicio"><x-heroicon-o-home/> <span>Início</span></a>
                    <a href="#workstation"><x-heroicon-o-squares-2x2/> <span>AI Workstation</span></a>
                    <a href="#agentes"><x-heroicon-o-user-group/> <span>Agentes IA</span></a>
                    <a href="#campanhas"><x-heroicon-o-megaphone/> <span>Campanhas</span></a>
                    <a href="#criativos"><x-heroicon-o-photo/> <span>Criativos</span></a>
                    <a href="#videos"><x-heroicon-o-video-camera/> <span>Vídeos</span></a>
                    <a href="#calendario"><x-heroicon-o-calendar-days/> <span>Calendário</span></a>
                    <a href="#distribuicao"><x-heroicon-o-paper-airplane/> <span>Distribuição</span></a>
                    <a href="#pipeline"><x-heroicon-o-funnel/> <span>Pipeline</span></a>
                    <a href="#qa"><x-heroicon-o-shield-check/> <span>QA e Aprovação</span></a>
                    <a href="#configuracoes"><x-heroicon-o-cog-6-tooth/> <span>Configurações</span></a>
                </nav>

                <div class="vm-side-card">
                    <div class="vm-side-spark">✦</div>
                    <strong>Marketing<br>que vende<br>com Inteligência.</strong>
                    <div class="vm-side-rule"></div>
                    <div class="vm-side-foot">Vitrine IA Pro<br>Centro de Marketing IA</div>
                </div>
            </aside>

            <main class="vm-main">
                <header class="vm-topbar">
                    <div class="vm-search"><x-heroicon-o-magnifying-glass style="width:18px;height:18px"/><span>Buscar campanhas, criativos, agentes...</span><span class="vm-key">⌘ K</span></div>
                    <div class="vm-top-actions">
                        <div class="vm-bell"><x-heroicon-o-bell style="width:21px;height:21px"/></div>
                        <div class="vm-user">
                            <div class="vm-avatar">{{ strtoupper(substr(auth()->user()?->name ?? 'CS',0,1).substr(strrchr(auth()->user()?->name ?? 'CS',' ') ?: 'S',1,1)) }}</div>
                            <div><div class="vm-user-name">{{ auth()->user()?->name ?? 'Administrador' }}</div><div class="vm-user-role">Admin</div></div>
                            <x-heroicon-o-chevron-down style="width:15px;height:15px;color:#817b92"/>
                        </div>
                    </div>
                </header>

                <div class="vm-content">
                    <section id="inicio" class="vm-hero">
                        <div class="vm-hero-copy">
                            <div class="vm-eyebrow">Vitrine IA Pro</div>
                            <h1>Marketing <span class="vm-gradient">IA</span></h1>
                            <div class="vm-hero-lead">Estratégia. Criatividade. Resultados.</div>
                            <div class="vm-hero-sub">Agentes de IA, campanhas inteligentes e conteúdo com controle humano.</div>
                            <a href="#copilot" class="vm-cta">Criar nova campanha <span>→</span></a>
                        </div>
                        <div class="vm-hero-brand">
                            <svg class="vm-big-mark" viewBox="0 0 120 110" aria-hidden="true"><defs><linearGradient id="v1" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#e879f9"/><stop offset=".46" stop-color="#8b5cf6"/><stop offset="1" stop-color="#4f46e5"/></linearGradient><linearGradient id="v2" x1="1" y1="0" x2="0" y2="1"><stop stop-color="#c084fc"/><stop offset="1" stop-color="#5b21b6"/></linearGradient></defs><path fill="url(#v1)" d="M10 12h50l-25 82z"/><path fill="url(#v2)" d="M55 12h55L73 89 51 49z"/><path fill="#17102f" opacity=".5" d="M35 30h38L53 72z"/></svg>
                            <div class="vm-big-brand">VITRINE IA <span>PRO</span></div>
                            <div class="vm-big-tag">Marketing que vende<br>com inteligência.</div>
                        </div>
                    </section>

                    <section id="workstation" class="vm-panel vm-workstation">
                        <div class="vm-workstation-head">
                            <div>
                                <div class="vm-eyebrow">Marketing IA Workstation</div>
                                <h2>Produção nativa da Vitrine IA Pro</h2>
                                <p>O Diretor de Marketing IA organiza o briefing, Gemini estrutura a direção, Veo/Gemini Image geram a mídia, FFmpeg finaliza a marca e o QA governa a aprovação antes da distribuição.</p>
                            </div>
                            <span class="vm-version">V2.0 · HML</span>
                        </div>

                        <div class="vm-modules" aria-label="Módulos do Fluxo Marketing IA">
                            <div class="vm-module">
                                <div class="vm-module-top"><div class="vm-module-icon"><x-heroicon-o-sparkles style="width:18px;height:18px"/></div><span class="vm-module-status active">ATIVO</span></div>
                                <div class="vm-module-name">Creative Studio · Marketing IA</div>
                                <div class="vm-module-desc">Gera briefing, direção, cenas, prompts e checklist para execução direta pelos motores nativos.</div>
                            </div>
                            <div class="vm-module">
                                <div class="vm-module-top"><div class="vm-module-icon"><x-heroicon-o-code-bracket style="width:18px;height:18px"/></div><span class="vm-module-status ready">GOVERNADO</span></div>
                                <div class="vm-module-name">Engineering Studio · Antigravity</div>
                                <div class="vm-module-desc">Fluxo oficial: GitHub → branch → testes → HML → QA. Sem alteração livre em produção.</div>
                            </div>
                            <div class="vm-module">
                                <div class="vm-module-top"><div class="vm-module-icon"><x-heroicon-o-beaker style="width:18px;height:18px"/></div><span class="vm-module-status ready">PRONTO</span></div>
                                <div class="vm-module-name">AI Lab · Gemini / AI Studio</div>
                                <div class="vm-module-desc">Laboratório para validar prompts, modelos e respostas antes de promover para os produtos.</div>
                            </div>
                            <div class="vm-module">
                                <div class="vm-module-top"><div class="vm-module-icon"><x-heroicon-o-folder-open style="width:18px;height:18px"/></div><span class="vm-module-status ready">ESTRUTURADO</span></div>
                                <div class="vm-module-name">Asset Library · Drive</div>
                                <div class="vm-module-desc">Destino oficial para marca, campanhas, referências, Reels, Stories, Feed e entregáveis aprovados.</div>
                            </div>
                        </div>

                        <div class="vm-flow-layout">
                            <div class="vm-flow-box">
                                <div class="vm-flow-title"><strong>Fluxo Marketing IA · Produção Nativa</strong><span>briefing → job → motor nativo → QA</span></div>
                                @if($flowError)<div class="vm-error">{{ $flowError }}</div>@endif

                                <div class="vm-bridge">
                                    <div class="vm-bridge-head"><strong>Motor de Produção Nativo</strong><span class="vm-bridge-state">OPERACIONAL</span></div>
                                    <div class="vm-flow-form">
                                        <div class="vm-field"><label>Direção / Copy</label><input value="Gemini · Marketing IA" readonly></div>
                                        <div class="vm-field"><label>Vídeo</label><input value="Veo 3.1 · API nativa" readonly></div>
                                        <div class="vm-field"><label>Imagem</label><input value="Gemini Image / Nano Banana" readonly></div>
                                        <div class="vm-field"><label>Finalização</label><input value="FFmpeg + Logo oficial + QA" readonly></div>
                                    </div>
                                    <div class="vm-note"><span>Google Flow não é mais dependência do caminho principal.</span><span>O Marketing IA prepara, gera, finaliza e encaminha a peça para QA.</span></div>
                                </div>

                                <div class="vm-flow-title"><strong>Creative Studio · Criar Job de Produção</strong><span>Gemini → direção → job nativo</span></div>
                                <form wire:submit="generateFlowPackage">
                                    <div class="vm-flow-form">
                                        <div class="vm-field full"><label for="flow-campaign">Campanha</label><input id="flow-campaign" wire:model="flowCampaign" maxlength="160" placeholder="Ex.: Vitrine Social Mídia"></div>
                                        <div class="vm-field full"><label for="flow-objective">Objetivo</label><input id="flow-objective" wire:model="flowObjective" maxlength="240" placeholder="Ex.: apresentar o produto e gerar interesse"></div>
                                        <div class="vm-field full"><label for="flow-audience">Público</label><input id="flow-audience" wire:model="flowAudience" maxlength="240"></div>
                                        <div class="vm-field"><label for="flow-format">Formato</label><select id="flow-format" wire:model="flowFormat"><option value="reel_9_16">Reel 9:16</option><option value="story_9_16">Story 9:16</option><option value="video_16_9">Vídeo 16:9</option><option value="ad_1_1">Criativo 1:1</option></select></div>
                                        <div class="vm-field"><label for="flow-duration">Duração</label><input id="flow-duration" wire:model="flowDuration" maxlength="80" placeholder="Ex.: 8 segundos"></div>
                                        <div class="vm-field full"><label for="flow-message">Mensagem principal</label><textarea id="flow-message" wire:model="flowMessage" maxlength="900" placeholder="O que essa peça precisa comunicar?"></textarea></div>
                                        <div class="vm-field"><label for="flow-cta">CTA</label><input id="flow-cta" wire:model="flowCta" maxlength="180" placeholder="Ex.: Conheça agora"></div>
                                        <div class="vm-field"><label for="flow-style">Estilo visual</label><input id="flow-style" wire:model="flowStyle" maxlength="240"></div>
                                    </div>
                                    <div class="vm-flow-actions">
                                        <button type="submit" class="vm-flow-primary" wire:loading.attr="disabled" wire:target="generateFlowPackage">Gerar Job de Produção</button>
                                        <button type="button" class="vm-flow-secondary" wire:click="clearFlowPackage">Novo rascunho</button>
                                    </div>
                                    <div class="vm-note"><span>O Marketing IA agora executa a geração diretamente pelos motores nativos.</span><span>Logo oficial: nunca gerado por IA; entra na finalização técnica controlada.</span><span>A geração consome API somente quando você clicar em Gerar mídia.</span></div>
                                </form>
                            </div>

                            <div class="vm-flow-box">
                                <div class="vm-flow-title"><strong>JOB DE PRODUÇÃO</strong><span>execução nativa e governança</span></div>
                                @if($flowJobId !== '')
                                    <div class="vm-job">
                                        <div class="vm-job-head"><div class="vm-job-id">{{ $flowJobId }}</div><span class="vm-job-status">{{ $flowJobStatus }}</span></div>
                                        <div class="vm-job-meta">{{ $flowCampaign }} · Motor nativo Marketing IA · Fonte: {{ $flowGenerationSource !== '' ? $flowGenerationSource : '—' }}</div>
                                        <div class="vm-job-actions">
                                            @if(in_array($nativeProductionStatus, ['PRONTO_PARA_PRODUCAO', 'RASCUNHO', 'ERRO'], true))
                                                <button type="button" wire:click="startNativeProduction" wire:loading.attr="disabled" wire:target="startNativeProduction">Gerar mídia</button>
                                            @endif
                                            @if($nativeProductionStatus === 'EM_GERACAO')
                                                <button type="button" wire:click="refreshNativeProduction" wire:loading.attr="disabled" wire:target="refreshNativeProduction"><span wire:loading.remove wire:target="refreshNativeProduction">Atualizar geração</span><span wire:loading wire:target="refreshNativeProduction">Consultando Veo...</span></button>
                                            @endif
                                            @if($nativeProductionStatus === 'GERADO')
                                                <button type="button" wire:click="finalizeNativeProduction" wire:loading.attr="disabled" wire:target="finalizeNativeProduction"><span wire:loading.remove wire:target="finalizeNativeProduction">Finalizar e enviar para QA</span><span wire:loading wire:target="finalizeNativeProduction">Finalizando vídeo...</span></button>
                                            @endif
                                            @if($nativeProductionStatus === 'EM_QA')
                                                <button type="button" wire:click="setFlowJobStatus('REPROVADO_QA')">Reprovar QA</button>
                                                <button type="button" wire:click="setFlowJobStatus('APROVADO')">Aprovar peça</button>
                                            @endif
                                        </div>
                                        @if($nativeProductionError)<div class="vm-error">{{ $nativeProductionError }}</div>@endif
                                        @if($nativeProductionPreviewUrl !== '')
                                            <div style="margin-top:12px;border-radius:16px;overflow:hidden;background:#080611;border:1px solid rgba(139,92,246,.22)">
                                                @if($flowFormat === 'ad_1_1')
                                                    <img src="{{ $nativeProductionPreviewUrl }}" alt="Criativo gerado pelo Marketing IA" style="display:block;width:100%;max-height:620px;object-fit:contain;background:#000">
                                                @else
                                                    <video controls playsinline preload="metadata" style="display:block;width:100%;max-height:520px;background:#000" src="{{ $nativeProductionPreviewUrl }}"></video>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="vm-note"><span>Motor: {{ $flowFormat === 'ad_1_1' ? 'Gemini Image / Nano Banana' : 'Veo 3.1' }} via API nativa.</span><span>Status: {{ $nativeProductionStatus }}</span>@if($nativeProductionStatus === 'GERADO')<span>Mídia-base pronta. A finalização foi separada para evitar travar a interface durante download/FFmpeg.</span>@elseif($nativeProductionStatus === 'EM_QA')<span>Marketing IA aplicou o logo oficial de forma determinística e a peça está pronta para QA.</span>@endif</div>
                                    </div>

                                    <div class="vm-operator">
                                        <div class="vm-flow-title"><strong>Fluxo de Produção Marketing IA</strong><span>fonte da verdade</span></div>
                                        <pre>JOB: {{ $flowJobId }}
CAMPANHA: {{ $flowCampaign }}
FORMATO: {{ $flowFormat }}
DURAÇÃO: {{ $flowDuration }}
MENSAGEM: {{ $flowMessage }}
CTA: {{ $flowCta !== '' ? $flowCta : 'NÃO INFORMADO' }}
MOTOR: Marketing IA / Veo 3.1
FINALIZAÇÃO: FFmpeg + logo oficial + QA</pre>
                                        <div class="vm-note"><span>Este Job de Produção é executado dentro do próprio Marketing IA; nenhum handoff para Google Flow é necessário.</span></div>
                                    </div>
                                @endif

                                <div class="vm-flow-title" style="margin-top:12px"><strong>Pacote de produção</strong><span>fonte do job</span></div>
                                @if($flowPackage !== '')
                                    <div class="vm-flow-output">{{ $flowPackage }}</div>
                                    <div class="vm-operator">
                                        <div class="vm-flow-title"><strong>Plano de Execução Nativa</strong><span>Marketing IA</span></div>
                                        <pre>{{ $flowPackage }}</pre>
                                    </div>
                                @else
                                    <div class="vm-flow-empty">Preencha o briefing e gere o primeiro Job de Produção. O Marketing IA devolverá direção criativa, cenas, câmera, áudio, texto, negative prompt, assets e checklist de QA prontos para os motores nativos.</div>
                                @endif

                                @if(count($flowJobs) > 0)
                                    <div class="vm-history">
                                        <div class="vm-flow-title"><strong>Últimos Jobs de Produção</strong><span>{{ count($flowJobs) }} em sessão</span></div>
                                        @foreach($flowJobs as $job)
                                            <div class="vm-history-row">
                                                <div>
                                                    <strong>{{ $job['id'] ?? '' }}</strong>
                                                    <span>{{ $job['campaign'] ?? '' }} · {{ ($job['legacy'] ?? false) ? 'Histórico Google Flow' : 'Produção nativa Marketing IA' }}</span>
                                                </div>
                                                <span>{{ $job['status'] ?? '' }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </section>

                    <section class="vm-stats" aria-label="Indicadores">
                        <div class="vm-stat"><div class="vm-stat-head"><div class="vm-stat-icon"><x-heroicon-o-megaphone style="width:24px;height:24px"/></div><div class="vm-stat-label">Campanhas Ativas</div></div><div class="vm-stat-value">{{ $activeCampaigns }}</div><div class="vm-stat-meta">Estado persistido atual</div></div>
                        <div class="vm-stat"><div class="vm-stat-head"><div class="vm-stat-icon"><x-heroicon-o-chart-bar style="width:24px;height:24px"/></div><div class="vm-stat-label">Alcance Total</div></div><div class="vm-stat-value">—</div><div class="vm-stat-meta">Sem métrica auditada</div></div>
                        <div class="vm-stat"><div class="vm-stat-head"><div class="vm-stat-icon"><x-heroicon-o-user-group style="width:24px;height:24px"/></div><div class="vm-stat-label">Leads Gerados</div></div><div class="vm-stat-value">—</div><div class="vm-stat-meta">Sem métrica auditada</div></div>
                        <div class="vm-stat"><div class="vm-stat-head"><div class="vm-stat-icon"><x-heroicon-o-banknotes style="width:24px;height:24px"/></div><div class="vm-stat-label">ROI Médio</div></div><div class="vm-stat-value">—</div><div class="vm-stat-meta">Sem métrica auditada</div></div>
                    </section>

                    <section class="vm-grid">
                        <div id="campanhas" class="vm-panel">
                            <div class="vm-panel-title"><h2>Campanhas Recentes</h2><a href="#copilot" class="vm-link">Criar campanha →</a></div>
                            <div class="vm-campaigns">
                                @if($campaign)
                                    <div class="vm-campaign-row"><div class="vm-thumb">✦</div><div><div class="vm-campaign-name">{{ $campaign['name'] ?: $campaign['public_id'] }}</div><div class="vm-campaign-type">Campanha persistida</div></div><span class="vm-status green">{{ $campaign['status'] }}</span><span class="vm-time">agora</span></div>
                                    <div class="vm-campaign-row"><div class="vm-thumb">AI</div><div><div class="vm-campaign-name">Pipeline operacional</div><div class="vm-campaign-type">{{ $taskCount }} tarefas registradas</div></div><span class="vm-status purple">Em monitoramento</span><span class="vm-time">atual</span></div>
                                @else
                                    <div class="vm-campaign-row"><div class="vm-thumb">+</div><div><div class="vm-campaign-name">Nenhuma campanha persistida</div><div class="vm-campaign-type">Crie a primeira campanha pelo Marketing IA</div></div><span class="vm-status blue">Pronto</span><span class="vm-time">—</span></div>
                                @endif
                                <div id="agentes" class="vm-campaign-row"><div class="vm-thumb">{{ $enabledAgents }}</div><div><div class="vm-campaign-name">Agentes IA habilitados</div><div class="vm-campaign-type">{{ count($agents) }} agentes registrados no Core</div></div><span class="vm-status blue">Operacional</span><span class="vm-time">runtime</span></div>
                                <div id="qa" class="vm-campaign-row"><div class="vm-thumb">✓</div><div><div class="vm-campaign-name">QA e Aprovação</div><div class="vm-campaign-type">Modo: {{ $runtime['approval_mode'] }}</div></div><span class="vm-status yellow">Controle humano</span><span class="vm-time">ativo</span></div>
                            </div>
                        </div>

                        <div class="vm-panel">
                            <div class="vm-panel-title"><h2>Desempenho das Campanhas</h2><span class="vm-secondary">Últimos 30 dias</span></div>
                            <div class="vm-chart">
                                <div class="vm-chart-grid"></div>
                                <svg viewBox="0 0 600 220" preserveAspectRatio="none" aria-label="Área reservada para série real de desempenho">
                                    <defs><linearGradient id="fadeP" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8b5cf6" stop-opacity=".24"/><stop offset="1" stop-color="#8b5cf6" stop-opacity="0"/></linearGradient></defs>
                                    <path d="M0 178 C90 154 135 166 210 134 S340 132 412 95 S520 88 600 56 L600 220 L0 220Z" fill="url(#fadeP)" opacity=".45"/>
                                    <path d="M0 178 C90 154 135 166 210 134 S340 132 412 95 S520 88 600 56" fill="none" stroke="#8b5cf6" stroke-width="3" opacity=".42"/>
                                    <path d="M0 194 C90 184 150 191 225 170 S360 180 435 146 S530 154 600 124" fill="none" stroke="#a855f7" stroke-width="2.2" opacity=".34"/>
                                    <path d="M0 204 C100 200 175 203 252 192 S390 190 468 174 S550 177 600 162" fill="none" stroke="#e879f9" stroke-width="2" opacity=".3"/>
                                </svg>
                                <div class="vm-empty-chart">Visual estrutural — série real ainda não auditada</div>
                            </div>
                            <div class="vm-legend"><span><i class="vm-dot" style="background:#7c3aed"></i>Visualizações</span><span><i class="vm-dot" style="background:#a855f7"></i>Leads</span><span><i class="vm-dot" style="background:#e879f9"></i>Conversões</span></div>
                        </div>
                    </section>

                    <section class="vm-quote"><div class="vm-quote-text">✦ &nbsp;“A combinação de IA e criatividade é o novo motor do crescimento.”</div><div class="vm-quote-brand"><span class="vm-quote-line"></span>Vitrine IA Pro</div></section>

                    <section id="copilot" class="vm-panel vm-copilot">
                        <div class="vm-copilot-head"><div><div class="vm-eyebrow">Diretor de Marketing IA</div><h2>Sessão de criação</h2><div style="margin-top:4px;font-size:10px;color:#706a82">{{ $copilotSessionId }}</div></div><button type="button" wire:click="newCopilotSession" class="vm-secondary">Nova sessão</button></div>
                        @if($copilotError)<div class="vm-error">{{ $copilotError }}</div>@endif
                        <div class="vm-chat">
                            @forelse($copilotMessages as $message)
                                <div class="vm-msg {{ ($message['role'] ?? '') === 'user' ? 'user' : 'ai' }}">{{ $message['content'] ?? '' }}</div>
                            @empty
                                <div class="vm-chat-empty">Converse com o Marketing IA para planejar campanhas, conteúdo, Reels e anúncios.</div>
                            @endforelse
                        </div>
                        <form wire:submit="sendCopilotMessage" class="vm-form"><textarea wire:model="copilotMessage" rows="3" maxlength="4000" placeholder="Digite o que deseja criar ou continuar..."></textarea><button type="submit" class="vm-send">Enviar</button></form>
                        <div class="vm-note"><span>Briefings estruturados com CAMPANHA, OBJETIVO, PÚBLICO, FORMATO, DURAÇÃO, MENSAGEM, CTA e ESTILO sincronizam automaticamente com o Job de Produção.</span><span id="distribuicao">Metricool: publicação orgânica somente após aprovação humana.</span><span>Windsor.ai / Meta Ads: ativação somente após autorização explícita de orçamento.</span></div>
                    </section>

                    <div style="height:1px;overflow:hidden"><span id="criativos"></span><span id="videos"></span><span id="calendario"></span><span id="pipeline"></span><span id="configuracoes"></span></div>
                </div>
            </main>
        </div>
    </div>
</x-filament-panels::page>
