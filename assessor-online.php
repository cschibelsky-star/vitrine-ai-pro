<?php
$title = 'Assessor Online | Atendimento e gestão inteligente para mandatos';
$description = 'Assessor Online: organize solicitações, acompanhe prazos, gere documentos e informe o cidadão sobre o andamento de demandas do mandato. Conheça o projeto da Vitrine IA Pro.';
require __DIR__ . '/includes/header.php';
?>
<style>
.ao-page{--ao-ink:#102443;--ao-accent:#17c4db;--ao-mute:#64748b;overflow:hidden}
.ao-page *, .ao-page *:before,.ao-page *:after{box-sizing:border-box}
.ao-page .ao-hero{background:radial-gradient(ellipse at 88% 18%,#163c68 0%,transparent 45%),linear-gradient(135deg,#07172c,#102c52);color:#fff;padding:clamp(64px,9vw,118px) max(24px,calc((100vw - 1160px)/2))}
.ao-page .ao-wrap{max-width:1160px;margin:auto;padding:0 24px}
.ao-page .ao-hero-inner{display:grid;grid-template-columns:1.08fr .92fr;align-items:center;gap:52px}
.ao-page .ao-eyebrow{display:inline-flex;align-items:center;gap:10px;font-size:.77rem;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:#67e3f2}
.ao-page h1{font-size:clamp(2.6rem,5.5vw,4.8rem);line-height:1.07;letter-spacing:-.045em;margin:18px 0 22px;color:inherit}
.ao-page h2{font-size:clamp(1.9rem,3.7vw,3rem);line-height:1.15;letter-spacing:-.03em;color:var(--ao-ink);margin:10px 0 18px}
.ao-page h3{font-size:1.18rem;line-height:1.35;color:var(--ao-ink);margin:0 0 10px}
.ao-page p{line-height:1.75}
.ao-page .ao-lead{color:#d3e0ee;font-size:1.14rem;max-width:630px}
.ao-page .ao-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:30px}
.ao-page .ao-button{display:inline-flex;align-items:center;justify-content:center;min-height:50px;border-radius:12px;padding:12px 21px;text-decoration:none;font-size:.97rem;font-weight:800;transition:transform .2s,background-color .2s}
.ao-page .ao-button:hover{transform:translateY(-2px)}
.ao-page .ao-button-primary{background:#36d7e6;color:#061b32}
.ao-page .ao-button-outline{border:1px solid #8bafcf;color:#fff}
.ao-page .ao-product-preview{border:1px solid #43678b;border-radius:22px;background:linear-gradient(145deg,#143557,#0d213a);box-shadow:0 24px 65px #0005;padding:22px}
.ao-page .ao-preview-head{display:flex;justify-content:space-between;gap:10px;align-items:center;padding-bottom:16px;border-bottom:1px solid #426184;font-size:.91rem}
.ao-page .ao-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#3cdfb9;margin-right:7px}
.ao-page .ao-preview-metrics{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:16px}
.ao-page .ao-preview-metrics div{padding:17px;border:1px solid #375777;background:#153554;border-radius:12px}
.ao-page .ao-preview-metrics strong{display:block;font-size:1.15rem;color:#f0fbff}
.ao-page .ao-preview-metrics span{display:block;font-size:.83rem;color:#bad0e2;padding-top:6px}
.ao-page .ao-preview-flow{display:grid;gap:8px;margin-top:18px}
.ao-page .ao-preview-flow div{border-radius:9px;background:#244765;color:#e9f9ff;padding:10px 12px;font-size:.86rem}
.ao-page .ao-caption{color:#abc1d5;font-size:.77rem;margin:15px 0 0}
.ao-page .ao-section{padding:clamp(64px,8vw,100px) 0}
.ao-page .ao-section-muted{background:#f1f6fa}
.ao-page .ao-intro{max-width:760px;color:#53657b;font-size:1.05rem;margin-bottom:28px}
.ao-page .ao-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:19px}
.ao-page .ao-card{background:#fff;border:1px solid #dce6ef;border-radius:17px;padding:26px;box-shadow:0 7px 23px #102b4608}
.ao-page .ao-card p{margin:0;color:#53657b;font-size:.96rem}
.ao-page .ao-icon{width:45px;height:45px;display:grid;place-items:center;background:#e2f8fb;color:#08647b;border-radius:11px;font-size:1.35rem;margin-bottom:22px}
.ao-page .ao-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:15px}
.ao-page .ao-step{border-top:3px solid #14bad1;padding:18px 12px 8px 0}
.ao-page .ao-step b{font-size:.85rem;color:#07869d}
.ao-page .ao-step p{color:#53657b;margin:7px 0 0;font-size:.94rem}
.ao-page .ao-note{border-left:4px solid #26b7cc;background:#ebf8fb;padding:16px 20px;border-radius:0 10px 10px 0;color:#274761;margin-top:24px}
.ao-page .ao-faq details{border-bottom:1px solid #d7e2ea;padding:17px 0}
.ao-page .ao-faq summary{font-weight:750;cursor:pointer;color:#102443;font-size:1.05rem}
.ao-page .ao-faq p{color:#53657b;margin:12px 0 0}
.ao-page .ao-final{background:linear-gradient(120deg,#0a2342,#123f62);color:#fff;padding:70px 0}
.ao-page .ao-final h2{color:#fff}
.ao-page .ao-final p{color:#d0e4f0;max-width:720px}
@media(max-width:850px){.ao-page .ao-hero-inner{grid-template-columns:1fr}.ao-page .ao-grid{grid-template-columns:repeat(2,1fr)}.ao-page .ao-steps{grid-template-columns:repeat(2,1fr)}}
@media(max-width:560px){.ao-page .ao-grid,.ao-page .ao-steps{grid-template-columns:1fr}.ao-page .ao-hero{padding:66px 0}.ao-page .ao-product-preview{padding:15px}.ao-page .ao-button{width:100%}.ao-page .ao-actions{width:100%}}
@media(prefers-reduced-motion:reduce){.ao-page .ao-button{transition:none}.ao-page .ao-button:hover{transform:none}}
</style>
<main class="ao-page">
<section class="ao-hero"><div class="ao-wrap ao-hero-inner">
<div><span class="ao-eyebrow">Vitrine IA Pro · Tecnologia para mandatos</span>
<h1>Assessor Online<span style="color:#54dbeb">.</span></h1>
<p style="font-size:1.5rem;font-weight:700;line-height:1.35;margin:0">Inteligência artificial para um mandato mais organizado, próximo e transparente.</p>
<p class="ao-lead">Centralize solicitações da população, acompanhe prazos, prepare documentos com apoio de IA e mantenha cada cidadão informado sobre o atendimento.</p>
<div class="ao-actions"><a class="ao-button ao-button-primary" href="/solicitacao-institucional.php?modelo=Assessor%20Online&perfil=C%C3%A2mara%20Municipal&finalidade=Solicitar%20apresenta%C3%A7%C3%A3o">Solicitar uma apresentação</a><a class="ao-button ao-button-outline" href="#como-funciona">Como funciona</a></div>
<p class="ao-caption">Apresentação comercial de produto em desenvolvimento. Recursos sujeitos à homologação.</p>
</div>
<div class="ao-product-preview" role="img" aria-label="Representação conceitual do fluxo de atendimento do Assessor Online, não corresponde a uma tela funcional do produto">
<div class="ao-preview-head"><strong>Visão do gabinete</strong><span><span class="ao-dot"></span> Fluxo ilustrativo</span></div>
<div class="ao-preview-metrics"><div><strong>Solicitações</strong><span>Registro centralizado</span></div><div><strong>Prazos</strong><span>Acompanhamento</span></div><div><strong>Documentos</strong><span>Apoio de IA</span></div><div><strong>Regiões</strong><span>Organização territorial</span></div></div>
<div class="ao-preview-flow"><div>01 · Cidadão registra uma demanda</div><div>02 · Equipe acompanha e encaminha</div><div>03 · Gabinete registra a resolução</div><div>04 · Solicitante recebe uma atualização</div></div>
<p class="ao-caption">Esquema conceitual — não são dados reais nem indicadores de desempenho.</p>
</div></div></section>
<section class="ao-section ao-section-muted" id="beneficios"><div class="ao-wrap"><span class="ao-eyebrow" style="color:#07869d">O desafio</span><h2>Seu gabinete precisa de continuidade, não de planilhas espalhadas.</h2>
<p class="ao-intro">Pedidos recebidos por diferentes canais podem perder contexto, prazos ou histórico. O Assessor Online foi concebido para organizar o ciclo completo de cada atendimento, com supervisão humana.</p>
<div class="ao-grid">
<article class="ao-card"><div class="ao-icon" aria-hidden="true">↳</div><h3>Atendimento centralizado</h3><p>Histórico de solicitações, protocolos e encaminhamentos reunidos por demanda.</p></article>
<article class="ao-card"><div class="ao-icon" aria-hidden="true">◷</div><h3>Controle de prazos</h3><p>Acompanhamento de pendências, responsáveis e próximas ações.</p></article>
<article class="ao-card"><div class="ao-icon" aria-hidden="true">✦</div><h3>Documentos com IA</h3><p>Apoio na elaboração de ofícios, requerimentos e comunicações, sempre com revisão da equipe.</p></article>
<article class="ao-card"><div class="ao-icon" aria-hidden="true">✓</div><h3>Retorno ao cidadão</h3><p>Registro das respostas e comunicação sobre a evolução ou conclusão do atendimento.</p></article>
<article class="ao-card"><div class="ao-icon" aria-hidden="true">⌖</div><h3>Visão por região</h3><p>Organização territorial das demandas para orientar prioridades do mandato.</p></article>
<article class="ao-card"><div class="ao-icon" aria-hidden="true">▣</div><h3>Gestão responsável dos dados</h3><p>Permissões, finalidade definida, histórico de consentimentos e controles de acesso projetados desde o início.</p></article>
</div></div></section>
<section class="ao-section" id="como-funciona"><div class="ao-wrap"><span class="ao-eyebrow" style="color:#07869d">Jornada de atendimento</span><h2>Da solicitação à devolutiva.</h2><p class="ao-intro">Um processo rastreável para apoiar assessores e equipes parlamentares.</p>
<div class="ao-steps"><div class="ao-step"><b>01 · Receber</b><h3>Registrar a demanda</h3><p>Identificar a solicitação, a região e os dados necessários ao atendimento.</p></div><div class="ao-step"><b>02 · Organizar</b><h3>Encaminhar e acompanhar</h3><p>Vincular responsáveis, atividades e prazos.</p></div><div class="ao-step"><b>03 · Resolver</b><h3>Documentar a resposta</h3><p>Preparar documentos, anexar evidências e validar providências.</p></div><div class="ao-step"><b>04 · Informar</b><h3>Dar retorno ao solicitante</h3><p>Comunicar o andamento ou a conclusão e manter o histórico.</p></div></div>
<div class="ao-note"><strong>Privacidade e comunicação:</strong> dados de atendimento não são autorização automática para mensagens promocionais ou político-eleitorais. Comunicações institucionais e informativas devem respeitar finalidades declaradas, direitos dos titulares e a legislação aplicável.</div></div></section>
<section class="ao-section ao-section-muted"><div class="ao-wrap"><span class="ao-eyebrow" style="color:#07869d">Para quem é</span><h2>Uma solução pensada para equipes de mandato.</h2><div class="ao-grid">
<article class="ao-card"><h3>Gabinetes de vereadores</h3><p>Estruture atendimentos locais, bairros, solicitações e retorno à população.</p></article>
<article class="ao-card"><h3>Gabinetes de deputados</h3><p>Consolide demandas de diferentes cidades, regiões e assessorias.</p></article>
<article class="ao-card"><h3>Equipes de assessoria</h3><p>Organize tarefas, documentos e responsabilidades em uma rotina compartilhada.</p></article>
</div></div></section>
<section class="ao-section"><div class="ao-wrap ao-faq"><span class="ao-eyebrow" style="color:#07869d">Perguntas frequentes</span><h2>O que você precisa saber.</h2>
<details><summary>A plataforma substitui a equipe do gabinete?</summary><p>Não. A proposta é apoiar organização, elaboração e acompanhamento, preservando decisões, supervisão e validação pela equipe responsável.</p></details>
<details><summary>É possível acompanhar o atendimento por bairro ou região?</summary><p>Essa é uma capacidade prevista no escopo do produto e depende da configuração do gabinete e de uma coleta de dados adequada à finalidade.</p></details>
<details><summary>A plataforma envia mensagens automaticamente?</summary><p>O fluxo proposto inclui atualizações sobre solicitações. Canais, automações, permissões e políticas de envio dependerão de integração e homologação.</p></details>
<details><summary>Já existem planos e preços?</summary><p>As condições comerciais serão apresentadas conforme o porte da equipe e o escopo contratado. Não há preço público definido nesta página.</p></details>
<details><summary>Como solicitar uma demonstração?</summary><p>Use o formulário de apresentação comercial para registrar o interesse. A equipe poderá analisar o contexto e apresentar a proposta adequada.</p></details>
</div></section>
<section class="ao-final" id="contato"><div class="ao-wrap"><span class="ao-eyebrow">Conheça o Assessor Online</span><h2>Organize o trabalho do gabinete. Aproxime o mandato das pessoas.</h2><p>Converse com a Vitrine IA Pro sobre o projeto, suas necessidades e as possibilidades de implantação.</p><div class="ao-actions"><a class="ao-button ao-button-primary" href="/solicitacao-institucional.php?modelo=Assessor%20Online&perfil=C%C3%A2mara%20Municipal">Quero conhecer a solução</a></div></div></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
