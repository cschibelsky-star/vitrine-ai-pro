<?php
$title='Demonstração | Assessor Online';
$description='Gabinete inteiramente fictício para apresentação da plataforma Assessor Online da Vitrine IA Pro.';
require __DIR__.'/includes/header.php';
$demands=[
 ['id'=>'DEMO-001','subject'=>'Iluminação pública','area'=>'Jardim Aurora','status'=>'Concluída','detail'=>'Solicitação registrada, encaminhamento simulado e devolutiva registrada.'],
 ['id'=>'DEMO-002','subject'=>'Transporte escolar','area'=>'Centro','status'=>'Em análise','detail'=>'Aguardando manifestação simulada do setor responsável.'],
 ['id'=>'DEMO-003','subject'=>'Acessibilidade em calçada','area'=>'Vila das Flores','status'=>'Encaminhada','detail'=>'Minuta ilustrativa de ofício disponível para revisão.'],
 ['id'=>'DEMO-004','subject'=>'Atendimento de saúde','area'=>'Jardim Aurora','status'=>'Pendente','detail'=>'Equipe deverá revisar os dados e indicar encaminhamento.']
];
?>
<style>
.ao-demo{background:#f2f6fa;color:#142840;min-height:75vh;padding:42px 22px 78px}.ao-demo *{box-sizing:border-box}.ao-demo-inner{max-width:1160px;margin:auto}
.ao-demo .demo-top{background:linear-gradient(120deg,#092542,#154567);border-radius:20px;padding:28px 30px;color:white;display:flex;justify-content:space-between;gap:24px;flex-wrap:wrap}
.ao-demo h1{font-size:clamp(2rem,4vw,3rem);line-height:1.15;color:#fff;margin:8px 0}
.ao-demo .demo-tag{display:inline-block;background:#e1fafc;color:#075a71;padding:6px 10px;border-radius:8px;font-size:.76rem;font-weight:800;letter-spacing:.07em}
.ao-demo .demo-desc{color:#d4e7f0;max-width:680px;margin:0}
.ao-demo .demo-note{padding:14px 18px;background:#fff6df;border:1px solid #eed597;border-radius:12px;margin:20px 0;color:#684715}
.ao-demo .demo-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:22px 0}
.ao-demo .demo-metric,.ao-demo .demo-card{background:#fff;border:1px solid #dce5ee;border-radius:15px;padding:22px;box-shadow:0 6px 22px #1e38500a}
.ao-demo .demo-metric b{font-size:1.65rem;color:#0e5671;display:block}.ao-demo .demo-metric span{color:#51687e;font-size:.88rem}
.ao-demo h2{font-size:1.5rem;color:#132b48;margin:30px 0 16px}
.ao-demo .demo-list{display:grid;gap:12px}
.ao-demo .demo-case{display:grid;grid-template-columns:1fr auto;gap:12px;background:#fff;border-radius:13px;padding:18px 22px;border:1px solid #dce5ee}
.ao-demo .demo-case strong{color:#163450}.ao-demo .demo-case p{color:#61758b;margin:6px 0 0;font-size:.91rem}
.ao-demo .demo-state{height:max-content;background:#e4f6f9;border-radius:99px;padding:5px 12px;color:#07677e;font-size:.77rem;font-weight:800}
.ao-demo .demo-bottom{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:24px}
.ao-demo .demo-card p{color:#53687d;line-height:1.6}
.ao-demo .demo-button{display:inline-block;padding:12px 18px;border-radius:10px;background:#086c83;color:#fff;text-decoration:none;font-weight:750}
@media(max-width:800px){.ao-demo .demo-grid{grid-template-columns:repeat(2,1fr)}.ao-demo .demo-bottom{grid-template-columns:1fr}}
@media(max-width:480px){.ao-demo .demo-grid{grid-template-columns:1fr 1fr}.ao-demo .demo-case{grid-template-columns:1fr}.ao-demo .demo-top{padding:22px}}
</style>
<main class="ao-demo"><div class="ao-demo-inner">
<div class="demo-top"><div><span class="demo-tag">DEMONSTRAÇÃO FICTÍCIA · SEM DADOS REAIS</span><h1>Gabinete do vereador Rafael Monteiro</h1><p class="demo-desc">Câmara Municipal de Vila Esperança · Gabinete 07 · cenário sintético para apresentar a jornada de solicitações do Assessor Online.</p></div><div><a class="demo-button" href="/assessor-online.php">Conhecer a solução</a></div></div>
<div class="demo-note"><strong>Atenção:</strong> esta é uma demonstração visual estática. Rafael Monteiro, Vila Esperança, solicitações, números e respostas são fictícios. Nenhuma solicitação é enviada a um órgão público e nenhuma publicação é realizada.</div>
<section aria-label="Indicadores simulados"><div class="demo-grid">
<div class="demo-metric"><b>128</b><span>Solicitações registradas</span></div>
<div class="demo-metric"><b>93</b><span>Concluídas</span></div>
<div class="demo-metric"><b>24</b><span>Em acompanhamento</span></div>
<div class="demo-metric"><b>11</b><span>Aguardando resposta</span></div>
</div></section>
<section><h2>Solicitações ilustrativas</h2><div class="demo-list">
<?php foreach($demands as $d): ?>
<article class="demo-case"><div><strong><?= htmlspecialchars($d['id'],ENT_QUOTES,'UTF-8') ?> · <?= htmlspecialchars($d['subject'],ENT_QUOTES,'UTF-8') ?></strong><p><?= htmlspecialchars($d['area'],ENT_QUOTES,'UTF-8') ?> · <?= htmlspecialchars($d['detail'],ENT_QUOTES,'UTF-8') ?></p></div><span class="demo-state"><?= htmlspecialchars($d['status'],ENT_QUOTES,'UTF-8') ?></span></article>
<?php endforeach; ?></div></section>
<section class="demo-bottom"><div class="demo-card"><h2>Fluxo proposto</h2><p>Registro → triagem → encaminhamento → acompanhamento de prazo → documentação → revisão humana → devolutiva ao solicitante.</p><p>Na plataforma real, cada etapa exigirá autenticação, permissões e trilha de auditoria.</p></div><div class="demo-card"><h2>Integração futura</h2><p>Uma resolução documentada poderá gerar uma sugestão de conteúdo para o Marketing IA. A equipe revisará o conteúdo antes de encaminhá-lo ao Vitrine Social Mídia para agendamento.</p><p>Não há publicação automática nem transferência de dados pessoais de cidadãos neste demonstrativo.</p></div></section>
</div></main>
<?php require __DIR__.'/includes/footer.php'; ?>
