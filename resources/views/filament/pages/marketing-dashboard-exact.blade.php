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
        $marketingContext = $this->getMarketingContext();
        $marketingContexts = $this->getMarketingContexts();
        $orchestrator = $this->getMediaOrchestratorStatus();
        $clientJobs = collect($flowJobs)->reject(fn (array $job) => (bool) ($job['legacy'] ?? false))->values();
        $imageJobs = $clientJobs->filter(fn (array $job) => (($job['type'] ?? (($job['format'] ?? '') === 'ad_1_1' ? 'image' : 'video')) === 'image'))->values();
        $videoJobs = $clientJobs->filter(fn (array $job) => (($job['type'] ?? (($job['format'] ?? '') === 'ad_1_1' ? 'image' : 'video')) === 'video'))->values();
        $workingJobs = $clientJobs->filter(fn (array $job) => in_array((string) ($job['status'] ?? ''), ['PRONTO_PARA_PRODUCAO','CORRECAO_SOLICITADA','EM_GERACAO','GERADO','FINALIZANDO'], true))->count();
        $reviewJobs = $clientJobs->filter(fn (array $job) => (string) ($job['status'] ?? '') === 'EM_QA')->count();
        $readyJobs = $clientJobs->filter(fn (array $job) => in_array((string) ($job['status'] ?? ''), ['APROVADO','PLANEJADO_EDITORIAL','AGENDADO','PUBLICADO'], true))->count();
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
        .vm-results { margin-top:18px;display:grid;gap:16px; }
        .vm-results-head { display:flex;align-items:flex-end;justify-content:space-between;gap:16px; }
        .vm-results-head h2 { margin:4px 0 0;font-size:24px;letter-spacing:-.035em; }
        .vm-results-head p { margin:6px 0 0;color:#9f9ab9;font-size:12px; }
        .vm-result-grid { display:grid;grid-auto-flow:column;grid-auto-columns:210px;grid-template-rows:1fr;gap:12px;overflow-x:auto;overflow-y:hidden;padding:2px 2px 8px;scroll-snap-type:x proximity;scrollbar-width:thin; }
        .vm-result-card { width:210px;min-width:210px;border:1px solid rgba(139,92,246,.16);border-radius:16px;overflow:hidden;background:#100d22;scroll-snap-align:start; }
        .vm-result-media { width:100%;height:210px;aspect-ratio:auto;background:#07050e;display:flex;align-items:center;justify-content:center;overflow:hidden; }
        .vm-result-media.video { width:100%;height:240px;max-height:240px;aspect-ratio:auto; }
        .vm-result-media img,.vm-result-media video { width:100%;height:100%;object-fit:contain;display:block; }
        .vm-result-body { padding:12px; }
        .vm-result-title { font-size:12px;font-weight:760;color:#fff; }
        .vm-result-meta { margin-top:5px;font-size:10px;color:#8f879f; }
        .vm-machine { display:grid;grid-template-columns:1fr auto;gap:18px;align-items:center;transition:.2s ease; }
        .vm-machine.is-requesting { border-color:rgba(168,85,247,.5);box-shadow:0 0 0 1px rgba(168,85,247,.12),0 18px 48px rgba(124,58,237,.16); }
        .vm-live-request { margin-top:12px;align-items:center;gap:10px;color:#d8d4e8;font-size:11px;font-weight:700; }
        .vm-live-dot { width:9px;height:9px;border-radius:50%;background:#a855f7;box-shadow:0 0 14px rgba(168,85,247,.9);animation:vmLiveDot 1s ease-in-out infinite; }
        @keyframes vmLiveDot { 0%,100%{transform:scale(.72);opacity:.45}50%{transform:scale(1.18);opacity:1} }
        .vm-machine-track { display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:14px; }
        .vm-machine-step { padding:12px 10px;border-radius:12px;background:rgba(255,255,255,.025);border:1px solid rgba(139,92,246,.12);font-size:10px;color:#837c98;text-align:center; }
        .vm-machine-step.active { color:#fff;border-color:rgba(139,92,246,.45);background:rgba(124,58,237,.16);box-shadow:0 0 22px rgba(124,58,237,.12); }
        .vm-machine-orb { position:relative;width:92px;height:92px;border-radius:50%;background:radial-gradient(circle at 35% 30%,#d8b4fe,#8b5cf6 38%,#312e81 72%,#0b0716);box-shadow:0 0 34px rgba(139,92,246,.45);animation:vmPulse 2.2s ease-in-out infinite; }
        .vm-machine-orb::after { content:"";position:absolute;inset:-10px;border-radius:50%;border:1px solid rgba(196,181,253,.24);animation:vmOrbit 2.8s linear infinite; }
        @keyframes vmPulse { 0%,100%{transform:scale(.96);opacity:.84}50%{transform:scale(1.04);opacity:1} }
        @keyframes vmOrbit { 0%{transform:scale(.92);opacity:.22}50%{transform:scale(1.12);opacity:.72}100%{transform:scale(.92);opacity:.22} }
        .vm-machine-step.active { position:relative;overflow:hidden; }
        .vm-machine-step.active::after { content:"";position:absolute;inset:0;transform:translateX(-120%);background:linear-gradient(90deg,transparent,rgba(255,255,255,.08),transparent);animation:vmStepSweep 2.4s ease-in-out infinite; }
        @keyframes vmStepSweep { 60%,100%{transform:translateX(120%)} }
        .vm-skeleton { position:relative;overflow:hidden;background:linear-gradient(180deg,rgba(255,255,255,.035),rgba(255,255,255,.015)); }
        .vm-skeleton::after { content:"";position:absolute;inset:0;transform:translateX(-110%);background:linear-gradient(90deg,transparent 0%,rgba(255,255,255,.05) 40%,rgba(196,181,253,.18) 50%,rgba(255,255,255,.05) 60%,transparent 100%);animation:vmShimmer 1.8s ease-in-out infinite; }
        @keyframes vmShimmer { 100%{transform:translateX(110%)} }
        .vm-loading-copy { position:relative;z-index:2;padding:18px;text-align:center;color:#b8b1c9;font-size:11px;line-height:1.55; }
        .vm-loading-copy strong { display:block;margin-bottom:6px;color:#f5f3ff;font-size:12px;font-weight:800; }
        .vm-status-badge { display:inline-flex;align-items:center;gap:6px;margin-top:8px;padding:5px 9px;border-radius:999px;font-size:9px;font-weight:800; }
        .vm-status-badge.processing { background:rgba(96,165,250,.12);color:#93c5fd; }
        .vm-status-badge.review { background:rgba(251,191,36,.12);color:#fde68a; }
        .vm-status-badge.ready { background:rgba(52,211,153,.12);color:#6ee7b7; }
        .vm-status-badge.error { background:rgba(248,113,113,.12);color:#fda4af; }
        @media (prefers-reduced-motion:reduce){.vm-machine-orb,.vm-machine-orb::after,.vm-machine-step.active::after,.vm-skeleton::after{animation:none!important}}
        .vm-tech details,.vm-tech summary { color:#8f879f; }
        .vm-tech summary { cursor:pointer;font-size:11px;list-style:none; }
        .vm-tech summary::-webkit-details-marker { display:none; }
        .vm-tech summary::before { content:"›";display:inline-block;margin-right:8px;transition:.18s; }
        .vm-tech[open] summary::before { transform:rotate(90deg); }
        .vm-section-empty { padding:24px;border:1px dashed rgba(139,92,246,.2);border-radius:14px;text-align:center;color:#817a92;font-size:12px; }
        .vm-action-link { display:inline-flex;margin-top:10px;padding:8px 12px;border-radius:999px;background:rgba(124,58,237,.16);color:#d8b4fe;text-decoration:none;font-size:11px;font-weight:700; }
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
        .vm-chat-archives { margin-top:12px;display:grid;gap:8px; }
        .vm-chat-archive { display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;padding:10px 12px;border-radius:11px;background:#100d24;border:1px solid rgba(139,92,246,.1); }
        .vm-chat-archive strong { display:block;color:#eee9fa;font-size:11px; }
        .vm-chat-archive span { display:block;margin-top:3px;color:#7f7893;font-size:9px; }
        .vm-chat-archive-actions { display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end; }
        .vm-chat-archive-actions button { padding:7px 9px;border-radius:8px;background:#19142f;border:1px solid rgba(139,92,246,.18);color:#c9c3dc;font-size:9px;font-weight:750; }
        .vm-context-bar { display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:14px;padding:12px 14px;border-radius:14px;background:#100d25;border:1px solid rgba(139,92,246,.16); }
        .vm-context-copy strong { display:block;color:white;font-size:12px; }
        .vm-context-copy span { display:block;margin-top:3px;color:#8f89a3;font-size:10px;line-height:1.45; }
        .vm-context-actions { display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end; }
        .vm-context-btn { padding:8px 11px;border-radius:10px;background:#19142f;border:1px solid rgba(139,92,246,.2);color:#bdb6cf;font-size:10px;font-weight:800; }
        .vm-context-btn.active { background:linear-gradient(135deg,#6d28d9,#8b5cf6);color:white;border-color:transparent;box-shadow:0 8px 22px rgba(124,58,237,.24); }

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
                    <a href="#resultados"><x-heroicon-o-squares-2x2/> <span>Resultados</span></a>
                    <a href="#agentes"><x-heroicon-o-user-group/> <span>Equipe IA</span></a>
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
                    <div class="vm-context-bar" aria-label="Contexto operacional do Marketing IA">
                        <div class="vm-context-copy">
                            <strong>{{ $marketingContext['label'] ?? 'Marketing IA' }}</strong>
                            <span>{{ $marketingContext['purpose'] ?? '' }}</span>
                        </div>
                        <div class="vm-context-actions">
                            @foreach($marketingContexts as $contextKey => $context)
                                <button type="button" class="vm-context-btn {{ $marketingContextKey === $contextKey ? 'active' : '' }}" wire:click="switchMarketingContext('{{ $contextKey }}')">
                                    {{ $context['label'] ?? $contextKey }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <section id="inicio" class="vm-hero">
                        <div class="vm-hero-copy">
                            <div class="vm-eyebrow">{{ ($marketingContext['mode'] ?? 'client') === 'engine' ? 'TV Digital Enterprise · Motor interno' : 'Cliente · TV Sumaré' }}</div>
                            <h1>Marketing <span class="vm-gradient">IA</span></h1>
                            <div class="vm-hero-lead">{{ ($marketingContext['mode'] ?? 'client') === 'engine' ? 'Motor da TV Digital.' : 'Marketing da TV Sumaré.' }}</div>
                            <div class="vm-hero-sub">{{ $marketingContext['purpose'] ?? 'Agentes de IA, campanhas inteligentes e conteúdo com controle humano.' }}</div>
                            <a href="#copilot" class="vm-cta">Criar nova campanha <span>→</span></a>
                        </div>
                        <div class="vm-hero-brand">
                            <svg class="vm-big-mark" viewBox="0 0 120 110" aria-hidden="true"><defs><linearGradient id="v1" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#e879f9"/><stop offset=".46" stop-color="#8b5cf6"/><stop offset="1" stop-color="#4f46e5"/></linearGradient><linearGradient id="v2" x1="1" y1="0" x2="0" y2="1"><stop stop-color="#c084fc"/><stop offset="1" stop-color="#5b21b6"/></linearGradient></defs><path fill="url(#v1)" d="M10 12h50l-25 82z"/><path fill="url(#v2)" d="M55 12h55L73 89 51 49z"/><path fill="#17102f" opacity=".5" d="M35 30h38L53 72z"/></svg>
                            <div class="vm-big-brand">VITRINE IA <span>PRO</span></div>
                            <div class="vm-big-tag">Marketing que vende<br>com inteligência.</div>
                        </div>
                    </section>

                    <section id="resultados" class="vm-results" wire:poll.5s="refreshProductionBoard">
                        @if($pieceError)<div class="vm-error" role="alert">{{ $pieceError }}</div>@endif
                        @if($pieceFeedback)<div class="vm-panel" role="status">{{ $pieceFeedback }}</div>@endif
                        <div class="vm-panel">
                            <div class="vm-results-head">
                                <div>
                                    <div class="vm-eyebrow">Resultado primeiro</div>
                                    <h2>O que o Marketing IA entregou</h2>
                                    <p>Revise cada peça, aprove a versão final e escolha o que fazer na galeria.</p>
                                </div>
                                <a href="#copilot" class="vm-cta" style="margin-top:0">Pedir criação ou ajuste <span>→</span></a>
                            </div>

                            <div class="vm-stats" style="margin-top:16px">
                                <div class="vm-stat"><div class="vm-stat-label">Em produção</div><div class="vm-stat-value">{{ $workingJobs }}</div><div class="vm-stat-meta">A máquina está trabalhando</div></div>
                                <div class="vm-stat"><div class="vm-stat-label">Para revisar</div><div class="vm-stat-value">{{ $reviewJobs }}</div><div class="vm-stat-meta">Aguardando sua decisão</div></div>
                                <div class="vm-stat"><div class="vm-stat-label">Prontos</div><div class="vm-stat-value">{{ $readyJobs }}</div><div class="vm-stat-meta">Aprovados, agendados ou publicados</div></div>
                                <div class="vm-stat"><div class="vm-stat-label">Conteúdos recentes</div><div class="vm-stat-value">{{ $clientJobs->count() }}</div><div class="vm-stat-meta">Nesta operação</div></div>
                            </div>
                        </div>

                        <div class="vm-panel vm-machine" wire:loading.class="is-requesting" wire:target="sendCopilotMessage">
                            <div>
                                <div class="vm-eyebrow">Marketing IA trabalhando</div>
                                <h2 style="margin:5px 0 0;font-size:18px">Sua equipe de IA está processando a operação</h2>
                                <div class="vm-machine-track">
                                    <div class="vm-machine-step {{ $clientJobs->count() > 0 ? 'active' : '' }}">Entendendo</div>
                                    <div class="vm-machine-step {{ $clientJobs->count() > 0 ? 'active' : '' }}">Planejando</div>
                                    <div class="vm-machine-step {{ $workingJobs > 0 ? 'active' : '' }}">Produzindo</div>
                                    <div class="vm-machine-step {{ $reviewJobs > 0 ? 'active' : '' }}">Revisando</div>
                                    <div class="vm-machine-step {{ $readyJobs > 0 ? 'active' : '' }}">Pronto</div>
                                </div>
                                <div class="vm-live-request" wire:loading.flex wire:target="sendCopilotMessage">
                                    <span class="vm-live-dot" aria-hidden="true"></span>
                                    <span>Entendendo seu pedido e montando a campanha...</span>
                                </div>
                                <div class="vm-note" style="margin-top:12px">
                                    <span>{{ $workingJobs > 0 ? $workingJobs.' conteúdo(s) em produção.' : 'Nenhuma produção ativa neste momento.' }}</span>
                                    <span>Use o campo de ajuste da peça que deseja corrigir.</span>
                                </div>
                            </div>
                            <div class="vm-machine-orb" aria-hidden="true"></div>
                        </div>

                        <div id="criativos" class="vm-panel">
                            <div class="vm-panel-title"><h2>Criativos</h2><a href="#copilot" class="vm-link">Pedir novo criativo →</a></div>
                            @if($imageJobs->count())
                                <div class="vm-result-grid">
                                    @foreach($imageJobs as $job)
                                        @php
                                            $media = trim((string) (($job['preview_url'] ?? '') ?: ($job['asset_url'] ?? '')));
                                            $rawStatus = (string) ($job['status'] ?? '');
                                            $statusView = match ($rawStatus) {
                                                'PRONTO_PARA_PRODUCAO', 'EM_GERACAO', 'FINALIZANDO' => ['label' => 'Produzindo', 'class' => 'processing'],
                                                'EM_QA' => ['label' => 'Em revisão', 'class' => 'review'],
                                                'GERADO' => ['label' => 'Aguardando aprovação', 'class' => 'review'],
                                                'APROVADO', 'PLANEJADO_EDITORIAL' => ['label' => 'Aprovado', 'class' => 'ready'],
                                                'AGENDADO', 'PUBLICADO' => ['label' => 'Distribuído', 'class' => 'ready'],
                                                'BLOQUEADO_CREDITO' => ['label' => 'Crédito indisponível', 'class' => 'error'],
                                                'ERRO' => ['label' => 'Atenção', 'class' => 'error'],
                                                default => ['label' => 'Aguardando', 'class' => 'processing'],
                                            };
                                        @endphp
                                        <article class="vm-result-card">
                                            <div class="vm-result-media">
                                                @if($media !== '')
                                                    <img src="{{ $media }}" alt="Criativo produzido pelo Marketing IA">
                                                @elseif(in_array((string) ($job['status'] ?? ''), ['PRONTO_PARA_PRODUCAO','EM_GERACAO','FINALIZANDO'], true))
                                                    <div class="vm-result-media vm-skeleton">
                                                        <div class="vm-loading-copy"><strong>Criando seu conteúdo…</strong>A IA está preparando esta peça para sua campanha.</div>
                                                    </div>
                                                @elseif((string) ($job['status'] ?? '') === 'EM_QA')
                                                    <div class="vm-loading-copy"><strong>Em revisão</strong>O conteúdo foi gerado e está aguardando validação.</div>
                                                @else
                                                    <div class="vm-loading-copy"><strong>Prévia ainda não disponível</strong>Assim que a peça estiver pronta, ela aparecerá aqui.</div>
                                                @endif
                                            </div>
                                            <div class="vm-result-body">
                                                <div class="vm-result-title">{{ $job['title'] ?? ($job['campaign'] ?? 'Criativo') }}</div>
                                                <div class="vm-result-meta">{{ $job['campaign'] ?? '' }}</div>
                                                <span class="vm-status-badge {{ $statusView['class'] }}">{{ $statusView['label'] }}</span>
                                                @if(!empty($job['error']))
                                                    <div style="margin-top:8px;color:#fda4af;font-size:10px;line-height:1.45">{{ $job['error'] }}</div>
                                                @endif
                                                @if(in_array((string) ($job['status'] ?? ''), ['ERRO','BLOQUEADO_CREDITO'], true))
                                                    <button type="button" class="vm-action-link" wire:click="retryProductionJob('{{ $job['id'] ?? '' }}')">Tentar novamente</button>
                                                @endif
                                                
                                                <div style="margin-top:12px">
                                                    @if(in_array($job['status'] ?? '', ['EM_QA', 'GERADO'], true))
                                                        <button type="button" class="vm-action-link" wire:click="approveProductionJob('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Aprovar esta versão</button>
                                                    @endif
                                                    @if(in_array($job['status'] ?? '', ['EM_QA', 'GERADO', 'APROVADO', 'PLANEJADO_EDITORIAL', 'REPROVADO_QA', 'ERRO'], true))
                                                        <label style="display:block;margin-top:10px;font-size:12px">Ajuste nesta peça
                                                            <textarea wire:model="pieceRevisionInputs.{{ $job['id'] }}" maxlength="2000" rows="2" style="width:100%;margin-top:6px;border-radius:8px;background:#17142e;color:#f7f5ff;border:1px solid #706a82" placeholder="Descreva somente o que deseja corrigir"></textarea>
                                                        </label>
                                                        <button type="button" class="vm-action-link" wire:click="requestPieceRevision('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Enviar correção desta peça</button>
                                                    @endif
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div class="vm-section-empty">Nenhum criativo disponível ainda. Quando uma imagem for produzida, ela aparecerá aqui.</div>
                            @endif
                        </div>

                        <div id="videos" class="vm-panel">
                            <div class="vm-panel-title"><h2>Vídeos</h2><a href="#copilot" class="vm-link">Pedir novo vídeo →</a></div>
                            @if($videoJobs->count())
                                <div class="vm-result-grid">
                                    @foreach($videoJobs as $job)
                                        @php
                                            $media = trim((string) (($job['preview_url'] ?? '') ?: ($job['asset_url'] ?? '')));
                                            $rawStatus = (string) ($job['status'] ?? '');
                                            $statusView = match ($rawStatus) {
                                                'PRONTO_PARA_PRODUCAO', 'EM_GERACAO', 'FINALIZANDO' => ['label' => 'Produzindo', 'class' => 'processing'],
                                                'EM_QA' => ['label' => 'Em revisão', 'class' => 'review'],
                                                'GERADO' => ['label' => 'Aguardando aprovação', 'class' => 'review'],
                                                'APROVADO', 'PLANEJADO_EDITORIAL' => ['label' => 'Aprovado', 'class' => 'ready'],
                                                'AGENDADO', 'PUBLICADO' => ['label' => 'Distribuído', 'class' => 'ready'],
                                                'BLOQUEADO_CREDITO' => ['label' => 'Crédito indisponível', 'class' => 'error'],
                                                'ERRO' => ['label' => 'Atenção', 'class' => 'error'],
                                                default => ['label' => 'Aguardando', 'class' => 'processing'],
                                            };
                                        @endphp
                                        <article class="vm-result-card">
                                            <div class="vm-result-media video">
                                                @if($media !== '')
                                                    <video controls playsinline preload="metadata" src="{{ $media }}"></video>
                                                @elseif(in_array((string) ($job['status'] ?? ''), ['PRONTO_PARA_PRODUCAO','EM_GERACAO','FINALIZANDO'], true))
                                                    <div class="vm-result-media video vm-skeleton">
                                                        <div class="vm-loading-copy"><strong>Seu vídeo está sendo produzido…</strong>O Marketing IA está montando esta peça agora.</div>
                                                    </div>
                                                @elseif((string) ($job['status'] ?? '') === 'EM_QA')
                                                    <div class="vm-loading-copy"><strong>Vídeo em revisão</strong>A geração terminou e a peça está aguardando aprovação.</div>
                                                @else
                                                    <div class="vm-loading-copy"><strong>Vídeo ainda não disponível</strong>Ele aparecerá aqui quando a produção terminar.</div>
                                                @endif
                                            </div>
                                            <div class="vm-result-body">
                                                <div class="vm-result-title">{{ $job['title'] ?? ($job['campaign'] ?? 'Vídeo') }}</div>
                                                <div class="vm-result-meta">{{ $job['campaign'] ?? '' }}</div>
                                                <span class="vm-status-badge {{ $statusView['class'] }}">{{ $statusView['label'] }}</span>
                                                @if(!empty($job['error']))
                                                    <div style="margin-top:8px;color:#fda4af;font-size:10px;line-height:1.45">{{ $job['error'] }}</div>
                                                @endif
                                                @if(in_array((string) ($job['status'] ?? ''), ['ERRO','BLOQUEADO_CREDITO'], true))
                                                    <button type="button" class="vm-action-link" wire:click="retryProductionJob('{{ $job['id'] ?? '' }}')">Tentar novamente</button>
                                                @endif
                                                
                                                <div style="margin-top:12px">
                                                    @if(in_array($job['status'] ?? '', ['EM_QA', 'GERADO'], true))
                                                        <button type="button" class="vm-action-link" wire:click="approveProductionJob('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Aprovar esta versão</button>
                                                    @endif
                                                    @if(in_array($job['status'] ?? '', ['EM_QA', 'GERADO', 'APROVADO', 'PLANEJADO_EDITORIAL', 'REPROVADO_QA', 'ERRO'], true))
                                                        <label style="display:block;margin-top:10px;font-size:12px">Ajuste nesta peça
                                                            <textarea wire:model="pieceRevisionInputs.{{ $job['id'] }}" maxlength="2000" rows="2" style="width:100%;margin-top:6px;border-radius:8px;background:#17142e;color:#f7f5ff;border:1px solid #706a82" placeholder="Descreva somente o que deseja corrigir"></textarea>
                                                        </label>
                                                        <button type="button" class="vm-action-link" wire:click="requestPieceRevision('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Enviar correção desta peça</button>
                                                    @endif
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div class="vm-section-empty">Nenhum vídeo disponível ainda. Os vídeos aparecem aqui conforme forem concluídos.</div>
                            @endif
                        </div>

                        <div id="calendario" class="vm-panel">
                            <div class="vm-panel-title"><h2>Calendário</h2><span class="vm-secondary">Conteúdo aprovado e programação</span></div>
                            @php $calendarJobs = collect($this->getGalleryJobs())->filter(fn (array $job) => !empty($job['scheduled_at'])); @endphp
                            @if($calendarJobs->count())
                                <div class="vm-campaigns">
                                    @foreach($calendarJobs as $job)
                                        <div class="vm-campaign-row"><div class="vm-thumb">✓</div><div><div class="vm-campaign-name">{{ $job['title'] ?? ($job['campaign'] ?? 'Conteúdo') }}</div><div class="vm-campaign-type">{{ $job['format'] ?? '' }}</div></div><span class="vm-status green">Planejamento editorial</span><span class="vm-time">{{ \Carbon\CarbonImmutable::parse($job['scheduled_at'])->setTimezone('America/Sao_Paulo')->format('d/m/Y H:i') }} BRT</span></div>
                                    @endforeach
                                </div>
                            @else
                                <div class="vm-section-empty">Nenhuma data editorial definida. Escolha uma peça na galeria para planejar a publicação.</div>
                            @endif
                        </div>

                        <div id="agentes" class="vm-panel">
                            <div class="vm-panel-title"><h2>Equipe IA</h2><span class="vm-secondary">Trabalhando em segundo plano</span></div>
                            <div class="vm-campaigns">
                                <div class="vm-campaign-row"><div class="vm-thumb">✦</div><div><div class="vm-campaign-name">Direção de Marketing</div><div class="vm-campaign-type">Transforma seu pedido em campanha e conteúdo</div></div><span class="vm-status purple">Ativa</span><span class="vm-time">IA</span></div>
                                <div class="vm-campaign-row"><div class="vm-thumb">✓</div><div><div class="vm-campaign-name">Revisão e qualidade</div><div class="vm-campaign-type">Confere o conteúdo antes da aprovação</div></div><span class="vm-status blue">Ativa</span><span class="vm-time">IA</span></div>
                                <div class="vm-campaign-row"><div class="vm-thumb">↗</div><div><div class="vm-campaign-name">Distribuição</div><div class="vm-campaign-type">Organiza o conteúdo aprovado para publicação</div></div><span class="vm-status yellow">Governada</span><span class="vm-time">IA</span></div>
                            </div>
                        </div>

                        <div id="qa" class="vm-panel">
                            <div class="vm-panel-title"><h2>QA e Aprovação</h2><a href="#copilot" class="vm-link">Pedir correção →</a></div>
                            @php $qaJobs = $clientJobs->filter(fn (array $job) => (string) ($job['status'] ?? '') === 'EM_QA'); @endphp
                            @if($qaJobs->count())
                                <div class="vm-result-grid">
                                    @foreach($qaJobs as $job)
                                        @php $media = trim((string) (($job['preview_url'] ?? '') ?: ($job['asset_url'] ?? ''))); @endphp
                                        <article class="vm-result-card">
                                            <div class="vm-result-media {{ (($job['type'] ?? '') === 'video') ? 'video' : '' }}">
                                                @if($media !== '' && (($job['type'] ?? '') === 'video'))
                                                    <video controls playsinline preload="metadata" src="{{ $media }}"></video>
                                                @elseif($media !== '')
                                                    <img src="{{ $media }}" alt="Conteúdo aguardando aprovação">
                                                @else
                                                    <div style="padding:18px;text-align:center;color:#8f879f;font-size:12px">Prévia em preparação</div>
                                                @endif
                                            </div>
                                            <div class="vm-result-body">
                                                <div class="vm-result-title">{{ $job['title'] ?? ($job['campaign'] ?? 'Conteúdo') }}</div>
                                                <div class="vm-result-meta">Aguardando sua revisão</div>
                                                
                                                <div style="margin-top:12px">
                                                    @if(in_array($job['status'] ?? '', ['EM_QA', 'GERADO'], true))
                                                        <button type="button" class="vm-action-link" wire:click="approveProductionJob('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Aprovar esta versão</button>
                                                    @endif
                                                    @if(in_array($job['status'] ?? '', ['EM_QA', 'GERADO', 'APROVADO', 'PLANEJADO_EDITORIAL', 'REPROVADO_QA', 'ERRO'], true))
                                                        <label style="display:block;margin-top:10px;font-size:12px">Ajuste nesta peça
                                                            <textarea wire:model="pieceRevisionInputs.{{ $job['id'] }}" maxlength="2000" rows="2" style="width:100%;margin-top:6px;border-radius:8px;background:#17142e;color:#f7f5ff;border:1px solid #706a82" placeholder="Descreva somente o que deseja corrigir"></textarea>
                                                        </label>
                                                        <button type="button" class="vm-action-link" wire:click="requestPieceRevision('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Enviar correção desta peça</button>
                                                    @endif
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div class="vm-section-empty">Nenhum conteúdo aguardando aprovação agora.</div>
                            @endif
                        </div>


                        <div id="distribuicao" class="vm-panel">
                            <div class="vm-panel-title"><h2>Galeria de aprovados</h2><span class="vm-secondary">Escolha quando distribuir</span></div>
                            <p class="vm-result-meta">As peças aprovadas ficam guardadas aqui. Publicação automática ainda não conectada.</p>
                            @php $galleryJobs = $this->getGalleryJobs(); @endphp
                            @if(count($galleryJobs))
                                <div class="vm-result-grid">
                                    @foreach($galleryJobs as $job)
                                        @php $media = ($job['preview_url'] ?? '') ?: ($job['asset_url'] ?? ''); @endphp
                                        <article class="vm-result-card" wire:key="gallery-{{ $job['id'] }}">
                                            <div class="vm-result-media {{ ($job['type'] ?? '') === 'video' ? 'video' : '' }}">
                                                @if(($job['type'] ?? '') === 'video')
                                                    <video controls playsinline preload="metadata" src="{{ $media }}"></video>
                                                @else
                                                    <img loading="lazy" src="{{ $media }}" alt="{{ $job['title'] ?? 'Peça aprovada' }}">
                                                @endif
                                            </div>
                                            <div class="vm-result-body">
                                                <h3 class="vm-result-title">{{ $job['title'] ?? 'Peça aprovada' }}</h3>
                                                <p class="vm-result-meta">{{ $job['campaign'] ?? '' }} · Versão {{ 1 + (int) ($job['revision_count'] ?? 0) }}</p>
                                                <p class="vm-result-meta">{{ $job['director_job']['caption'] ?? '' }}</p>
                                                <span class="vm-status-badge ready">{{ ($job['status'] ?? '') === 'PLANEJADO_EDITORIAL' ? 'Data editorial definida' : 'Aprovada' }}</span>
                                                <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:10px">
                                                    <button type="button" class="vm-action-link" wire:click="publishPieceNow('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Publicar agora</button>
                                                    <button type="button" class="vm-action-link" wire:click="keepPieceInGallery('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Guardar sem data</button>
                                                </div>
                                                <label style="display:block;margin-top:12px;font-size:12px">Data editorial — horário de Brasília
                                                    <input type="datetime-local" wire:model="pieceScheduleInputs.{{ $job['id'] }}" style="width:100%;margin-top:6px;background:#17142e;color:#f7f5ff;border:1px solid #706a82;border-radius:8px">
                                                </label>
                                                <button type="button" class="vm-action-link" wire:click="planPiecePublication('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Salvar data editorial</button>
                                                <p class="vm-result-meta">Esta data não ativa publicação automática.</p>
                                                
                                                <div style="margin-top:12px">
                                                    @if(in_array($job['status'] ?? '', ['EM_QA', 'GERADO'], true))
                                                        <button type="button" class="vm-action-link" wire:click="approveProductionJob('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Aprovar esta versão</button>
                                                    @endif
                                                    @if(in_array($job['status'] ?? '', ['EM_QA', 'GERADO', 'APROVADO', 'PLANEJADO_EDITORIAL', 'REPROVADO_QA', 'ERRO'], true))
                                                        <label style="display:block;margin-top:10px;font-size:12px">Ajuste nesta peça
                                                            <textarea wire:model="pieceRevisionInputs.{{ $job['id'] }}" maxlength="2000" rows="2" style="width:100%;margin-top:6px;border-radius:8px;background:#17142e;color:#f7f5ff;border:1px solid #706a82" placeholder="Descreva somente o que deseja corrigir"></textarea>
                                                        </label>
                                                        <button type="button" class="vm-action-link" wire:click="requestPieceRevision('{{ $job['id'] }}', '{{ $this->getPieceVersion($job) }}')" wire:loading.attr="disabled">Enviar correção desta peça</button>
                                                    @endif
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div class="vm-section-empty">Aprove uma versão para guardá-la na galeria.</div>
                            @endif
                        </div>

                        <div id="pipeline" class="vm-panel">
                            <div class="vm-panel-title"><h2>Andamento</h2><span class="vm-secondary">Visão simples do trabalho</span></div>
                            <div class="vm-machine-track">
                                <div class="vm-machine-step {{ $clientJobs->count() > 0 ? 'active' : '' }}">Pedido recebido</div>
                                <div class="vm-machine-step {{ $workingJobs > 0 ? 'active' : '' }}">Em produção</div>
                                <div class="vm-machine-step {{ $reviewJobs > 0 ? 'active' : '' }}">Para revisar</div>
                                <div class="vm-machine-step {{ $readyJobs > 0 ? 'active' : '' }}">Aprovado</div>
                                <div class="vm-machine-step {{ $clientJobs->contains(fn (array $job) => in_array((string) ($job['status'] ?? ''), ['AGENDADO','PUBLICADO'], true)) ? 'active' : '' }}">Distribuição</div>
                            </div>
                        </div>

                        <div id="configuracoes" class="vm-panel">
                            @php $publisher = $this->getMetaPublisherStatus(); @endphp
                            <div class="vm-panel-title"><h2>Configurações</h2><span class="vm-secondary">Contas e preferências</span></div>

                            @if(session('publisher_success'))
                                <div class="vm-note" style="margin-bottom:12px"><span>{{ session('publisher_success') }}</span></div>
                            @endif
                            @if(session('publisher_error'))
                                <div class="vm-error" style="margin-bottom:12px">{{ session('publisher_error') }}</div>
                            @endif
                            @if($pieceFeedback)
                                <div class="vm-note" style="margin-bottom:12px"><span>{{ $pieceFeedback }}</span></div>
                            @endif
                            @if($pieceError)
                                <div class="vm-error" style="margin-bottom:12px">{{ $pieceError }}</div>
                            @endif

                            <div style="border:1px solid rgba(139,92,246,.18);border-radius:14px;padding:16px;background:#100d24;margin-bottom:14px">
                                <div class="vm-panel-title" style="margin-bottom:10px">
                                    <div>
                                        <h2 style="font-size:15px">Contas Publicadoras</h2>
                                        <div class="vm-result-meta" style="margin-top:4px">Conecte Instagram/Facebook por autorização Meta. Tokens nunca aparecem na tela.</div>
                                    </div>
                                    <span class="vm-status {{ ($publisher['connected'] ?? false) ? 'green' : 'yellow' }}">
                                        {{ ($publisher['connected'] ?? false) ? 'CONECTADA' : 'NÃO CONECTADA' }}
                                    </span>
                                </div>

                                @if(!($publisher['app_configured'] ?? false))
                                    <div class="vm-error" style="margin:8px 0 12px">
                                        O aplicativo Meta ainda não está configurado no servidor. A tela de conexão está pronta, mas o botão será habilitado após cadastrar App ID, App Secret e versão da Graph API.
                                    </div>
                                @endif

                                @if($publisher['connected'] ?? false)
                                    <div class="vm-campaigns">
                                        @foreach(($publisher['accounts'] ?? []) as $account)
                                            @php $selected = ($publisher['selected_page_id'] ?? '') === ($account['page_id'] ?? ''); @endphp
                                            <div class="vm-campaign-row">
                                                <div class="vm-thumb">{{ $selected ? '✓' : 'M' }}</div>
                                                <div>
                                                    <div class="vm-campaign-name">{{ $account['page_name'] ?? 'Página Meta' }}</div>
                                                    <div class="vm-campaign-type">
                                                        Facebook: {{ $account['page_id'] ?? '—' }}
                                                        @if(!empty($account['instagram_user_id']))
                                                            · Instagram: {{ !empty($account['instagram_username']) ? '@'.$account['instagram_username'] : $account['instagram_user_id'] }}
                                                        @else
                                                            · Instagram não vinculado
                                                        @endif
                                                    </div>
                                                </div>
                                                @if($selected)
                                                    <span class="vm-status green">Padrão</span>
                                                @else
                                                    <button type="button" class="vm-action-link" wire:click="selectMetaPublisherAccount('{{ $account['page_id'] }}')" wire:loading.attr="disabled">Usar esta conta</button>
                                                @endif
                                                <span class="vm-time">Meta</span>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:14px">
                                        <button type="button" class="vm-action-link" wire:click="testMetaPublisherConnection" wire:loading.attr="disabled">Testar conexão</button>
                                        <a class="vm-action-link" href="{{ route('marketing.publisher.meta.connect') }}">Reconectar Meta</a>
                                        <form method="POST" action="{{ route('marketing.publisher.meta.disconnect') }}" onsubmit="return confirm('Desconectar a conta Meta deste Marketing IA?')">
                                            @csrf
                                            <button type="submit" class="vm-action-link">Desconectar</button>
                                        </form>
                                    </div>
                                @else
                                    @if($publisher['app_configured'] ?? false)
                                        <a class="vm-flow-primary" style="display:inline-flex;text-decoration:none;margin-top:8px" href="{{ route('marketing.publisher.meta.connect') }}">Conectar conta Meta</a>
                                    @else
                                        <button type="button" class="vm-flow-secondary" style="margin-top:8px" disabled>Conectar conta Meta</button>
                                    @endif
                                @endif
                            </div>

                            <div class="vm-campaigns">
                                <div class="vm-campaign-row"><div class="vm-thumb">◎</div><div><div class="vm-campaign-name">Marca ativa</div><div class="vm-campaign-type">{{ $marketingContext['brand'] ?? 'Marketing IA' }}</div></div><span class="vm-status purple">Ativa</span><span class="vm-time">contexto</span></div>
                                <div class="vm-campaign-row"><div class="vm-thumb">✦</div><div><div class="vm-campaign-name">Correções e preferências</div><div class="vm-campaign-type">Faça pedidos e ajustes pelo Diretor de Marketing IA</div></div><span class="vm-status blue">Via chat</span><span class="vm-time">sempre</span></div>
                            </div>
                        </div>
                    </section>

                    <details class="vm-tech" style="margin-top:16px">
                        <summary>Área técnica e diagnóstico</summary>
                        <section id="workstation" class="vm-panel vm-workstation" style="margin-top:10px">
                        <div class="vm-workstation-head">
                            <div>
                                <div class="vm-eyebrow">Marketing IA Workstation</div>
                                <h2>Produção nativa da Vitrine IA Pro</h2>
                                <p>O Diretor organiza o briefing, o Centro IA escolhe dinamicamente o melhor motor disponível por mídia, FFmpeg finaliza quando necessário e o QA governa a aprovação antes da distribuição.</p>
                            </div>
                            <span class="vm-version">V2.0 · HML</span>
                        </div>

                        <div class="vm-panel" style="margin:0 0 16px;background:#100d24;border-color:rgba(139,92,246,.2)">
                            <div class="vm-panel-title" style="margin-bottom:10px">
                                <h2>Orquestrador de mídia · Centro IA</h2>
                                @if(($orchestrator['ok'] ?? false) === true)
                                    <span class="vm-status green">SINCRONIZADO</span>
                                @else
                                    <span class="vm-status yellow">INDISPONÍVEL</span>
                                @endif
                            </div>

                            @if(($orchestrator['ok'] ?? false) === true)
                                @php
                                    $imageAvailability = (array) data_get($orchestrator, 'availability.image', []);
                                    $videoAvailability = (array) data_get($orchestrator, 'availability.video', []);
                                    $imageCandidates = array_slice((array) ($orchestrator['image_candidates'] ?? []), 0, 5);
                                    $videoCandidates = array_slice((array) ($orchestrator['video_candidates'] ?? []), 0, 5);
                                @endphp
                                <div class="vm-note" style="margin-bottom:10px">
                                    <span>Perfil: {{ $orchestrator['profile'] ?? 'balanced' }}</span>
                                    <span>Imagem: {{ $imageAvailability['available'] ?? 0 }} disponível(is)</span>
                                    <span>Vídeo: {{ $videoAvailability['available'] ?? 0 }} disponível(is)</span>
                                    <span>Endpoint imagem: {{ data_get($orchestrator, 'endpoints.images') ? 'ativo' : 'indisponível' }}</span>
                                    <span>Endpoint vídeo: {{ data_get($orchestrator, 'endpoints.videos') ? 'ativo' : 'indisponível' }}</span>
                                </div>

                                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px">
                                    @foreach(['Imagem' => $imageCandidates, 'Vídeo' => $videoCandidates] as $mediaLabel => $candidates)
                                        <div style="border:1px solid rgba(139,92,246,.14);border-radius:12px;padding:12px;background:#0b0918">
                                            <strong style="font-size:12px;color:#f4f0ff">{{ $mediaLabel }}</strong>
                                            <div style="display:grid;gap:7px;margin-top:9px">
                                                @forelse($candidates as $index => $candidate)
                                                    <div style="display:grid;grid-template-columns:24px minmax(0,1fr) auto;gap:8px;align-items:center;font-size:10px">
                                                        <span style="color:#8b5cf6;font-weight:800">#{{ $index + 1 }}</span>
                                                        <span style="color:#c9c3dc;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $candidate['reason'] ?? '' }}">{{ $candidate['model'] ?? '—' }}</span>
                                                        <span style="color:#6ee7b7;font-weight:700">{{ number_format((float) ($candidate['score'] ?? 0), 2, ',', '.') }}</span>
                                                    </div>
                                                @empty
                                                    <div style="font-size:10px;color:#8f879f">Nenhum candidato executável agora.</div>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="vm-note" style="margin-top:10px">
                                    <span>Fonte: Centro IA/Core. Atualização com cache de 60 segundos.</span>
                                    <span>OpenRouter: {{ data_get($orchestrator, 'providers.openrouter.configured') ? 'configurado para texto/raciocínio' : 'sem runtime ativo' }}.</span>
                                    <span>Rota de estratégia: {{ implode(' → ', (array) data_get($orchestrator, 'text_routes.marketing_strategy', [])) ?: 'não informada' }}.</span>
                                    <span>O Marketing IA não fixa Veo, Grok, Seedream ou Seedance: o motor é selecionado pela matriz operacional.</span>
                                </div>
                            @else
                                <div class="vm-error" style="margin:0">Centro IA não retornou o estado do orquestrador: {{ $orchestrator['error'] ?? 'erro desconhecido' }}.</div>
                            @endif
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
                                        <div class="vm-field"><label>Vídeo</label><input value="Centro IA · seleção dinâmica" readonly></div>
                                        <div class="vm-field"><label>Imagem</label><input value="Centro IA · seleção dinâmica" readonly></div>
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
                                    <div class="vm-note"><span>O Marketing IA executa a geração diretamente pelos motores nativos assim que o Job é criado.</span><span>Logo oficial: nunca gerado por IA; entra na finalização técnica controlada.</span><span>Gerar mídia fica disponível apenas como fallback/retry se uma execução automática falhar.</span></div>
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
                                                <a href="#qa" class="vm-action-link">Solicitar correção na peça</a>
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
                                    <div class="vm-history" wire:poll.15s="refreshProductionBoard">
                                        <div class="vm-flow-title">
                                            <strong>Produção em andamento / Conteúdos gerados</strong>
                                            <span>{{ count($flowJobs) }} job(s) · atualização automática</span>
                                        </div>

                                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin-top:12px">
                                            @foreach($flowJobs as $job)
                                                @php
                                                    $jobStatus = (string) ($job['status'] ?? '');
                                                    $jobType = (string) ($job['type'] ?? (($job['format'] ?? '') === 'ad_1_1' ? 'image' : 'video'));
                                                    $jobPreview = trim((string) ($job['preview_url'] ?? ''));
                                                    $jobAsset = trim((string) ($job['asset_url'] ?? ''));
                                                    $jobMedia = $jobPreview !== '' ? $jobPreview : $jobAsset;
                                                @endphp
                                                <article style="border:1px solid rgba(139,92,246,.22);border-radius:16px;padding:12px;background:rgba(12,9,24,.72)">
                                                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                                                        <div style="min-width:0">
                                                            <strong style="display:block;font-size:12px;word-break:break-all">{{ $job['id'] ?? '' }}</strong>
                                                            <span style="display:block;margin-top:4px;font-size:11px;color:#918aa8">{{ $job['title'] ?? ($job['campaign'] ?? 'Campanha') }}</span>
                                                        </div>
                                                        <span class="vm-job-status">{{ $jobStatus }}</span>
                                                    </div>

                                                    <div style="margin-top:10px;aspect-ratio:{{ $jobType === 'image' ? '1 / 1' : '9 / 16' }};max-height:380px;border-radius:12px;overflow:hidden;background:#05040a;display:flex;align-items:center;justify-content:center">
                                                        @if($jobMedia !== '')
                                                            @if($jobType === 'image')
                                                                <img src="{{ $jobMedia }}" alt="Conteúdo gerado pelo Marketing IA" style="display:block;width:100%;height:100%;object-fit:contain">
                                                            @else
                                                                <video controls playsinline preload="metadata" src="{{ $jobMedia }}" style="display:block;width:100%;height:100%;object-fit:contain"></video>
                                                            @endif
                                                        @elseif($jobStatus === 'EM_GERACAO')
                                                            <div style="padding:18px;text-align:center;color:#b7afc9;font-size:12px">
                                                                <div style="font-size:24px;margin-bottom:8px">✦</div>
                                                                Gerando conteúdo no motor selecionado pelo Centro IA...
                                                            </div>
                                                        @elseif($jobStatus === 'ERRO')
                                                            <div style="padding:18px;text-align:center;color:#f0a7a7;font-size:12px">Falha na geração. Consulte a mensagem abaixo.</div>
                                                        @else
                                                            <div style="padding:18px;text-align:center;color:#8f879f;font-size:12px">Aguardando mídia.</div>
                                                        @endif
                                                    </div>

                                                    <div style="margin-top:10px;font-size:11px;color:#8f879f">
                                                        {{ $job['campaign'] ?? '' }} · {{ $job['format'] ?? '' }}
                                                    </div>

                                                    @if(!empty($job['error']))
                                                        <div class="vm-error" style="margin-top:8px">{{ $job['error'] }}</div>
                                                    @endif

                                                    @if($jobMedia !== '')
                                                        <div class="vm-note" style="margin-top:8px">
                                                            <span>Conteúdo disponível para visualização.</span>
                                                            @if(in_array($jobStatus, ['EM_QA', 'GERADO', 'APROVADO'], true))
                                                                <span>Status atual: {{ $jobStatus }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </article>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <div class="vm-flow-empty">Nenhum Job de produção nesta sessão. Quando o Diretor materializar uma campanha, os conteúdos aparecerão aqui automaticamente.</div>
                                @endif
                            </div>
                        </div>
                        </section>
                    </details>

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
                                <div class="vm-campaign-row"><div class="vm-thumb">{{ $enabledAgents }}</div><div><div class="vm-campaign-name">Agentes IA habilitados</div><div class="vm-campaign-type">{{ count($agents) }} agentes registrados no Core</div></div><span class="vm-status blue">Operacional</span><span class="vm-time">runtime</span></div>
                                <div class="vm-campaign-row"><div class="vm-thumb">✓</div><div><div class="vm-campaign-name">QA e Aprovação</div><div class="vm-campaign-type">Modo: {{ $runtime['approval_mode'] }}</div></div><span class="vm-status yellow">Controle humano</span><span class="vm-time">ativo</span></div>
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
                        <div class="vm-copilot-head"><div><div class="vm-eyebrow">Diretor de Marketing IA</div><h2>Nova solicitação</h2><div style="margin-top:4px;font-size:10px;color:#706a82">{{ $copilotSessionId }}</div></div><button type="button" wire:click="releaseCopilotChat" class="vm-secondary">Limpar chat ativo</button></div>
                        @if($copilotError)<div class="vm-error">{{ $copilotError }}</div>@endif
                        <div class="vm-chat">
                            @forelse($copilotMessages as $message)
                                <div class="vm-msg {{ ($message['role'] ?? '') === 'user' ? 'user' : 'ai' }}">{{ $message['content'] ?? '' }}</div>
                            @empty
                                <div class="vm-chat-empty">Digite apenas o que deseja criar. Quando a produção for iniciada, a conversa será arquivada e este chat ficará livre para a próxima solicitação.</div>
                            @endforelse
                        </div>
                        <form wire:submit="sendCopilotMessage" class="vm-form"><textarea wire:model="copilotMessage" rows="3" maxlength="4000" placeholder="Ex.: crie uma campanha de divulgação da TV Sumaré para Instagram"></textarea><button type="submit" class="vm-send" wire:loading.attr="disabled" wire:target="sendCopilotMessage"><span wire:loading.remove wire:target="sendCopilotMessage">Enviar</span><span wire:loading wire:target="sendCopilotMessage">Criando campanha...</span></button></form>

                        @if(count($copilotArchives) > 0)
                            <div class="vm-chat-archives">
                                <div class="vm-flow-title" style="margin-bottom:0"><strong>Histórico de campanhas</strong><span>{{ count($copilotArchives) }} arquivada(s)</span></div>
                                @foreach($copilotArchives as $archive)
                                    <div class="vm-chat-archive">
                                        <div>
                                            <strong>{{ $archive['campaign'] ?? 'Campanha' }}</strong>
                                            <span>{{ $archive['summary'] ?? '' }}</span>
                                        </div>
                                        <div class="vm-chat-archive-actions">
                                            <button type="button" wire:click="viewCopilotArchive('{{ $archive['id'] ?? '' }}')">Ver conversa</button>
                                            <button type="button" wire:click="continueCopilotArchive('{{ $archive['id'] ?? '' }}')">Continuar campanha</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div class="vm-note"><span>Após materializar a campanha, o chat ativo é liberado automaticamente e o histórico fica disponível aqui.</span><span>Publicação orgânica somente após aprovação humana.</span><span>Windsor.ai / Meta Ads: ativação somente após autorização explícita de orçamento.</span></div>
                    </section>

                    <div style="height:1px;overflow:hidden"><span id="criativos"></span><span id="videos"></span><span id="calendario"></span><span id="pipeline"></span><span id="configuracoes"></span></div>
                </div>
            </main>
        </div>
    </div>
</x-filament-panels::page>
