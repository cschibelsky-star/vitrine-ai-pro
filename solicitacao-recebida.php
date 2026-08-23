<?php
$title='Solicitação Recebida';
require __DIR__.'/includes/header.php';
$info=$_SESSION['solicitacao_recebida'] ?? null;
if(!$info){ echo '<section class="section"><h1>Solicitação não localizada.</h1><p class="lead">Se você acabou de enviar o formulário, tente novamente.</p><a class="btn" href="/solicitacao-institucional.php">Voltar ao formulário</a></section>'; require __DIR__.'/includes/footer.php'; exit; }
unset($_SESSION['solicitacao_recebida']);
?>
<section class="section"><span class="eyebrow dark">Solicitação recebida</span><h1>Obrigado, <?= e($info['nome']) ?>.</h1><p class="lead">Sua solicitação sobre <strong><?= e($info['modelo']) ?></strong> foi registrada com o protocolo <strong><?= e($info['id']) ?></strong>.</p><div class="card"><h3>Próxima etapa</h3><p>Nossa equipe comercial analisará o contexto informado para preparar a orientação, demonstração ou proposta adequada.</p><?php if(($info['master'] ?? '0')!=='1'): ?><p class="note">Seu pedido foi registrado com sucesso e seguirá normalmente para análise pela equipe comercial.</p><?php endif; ?><div class="actions"><a class="btn" href="/index.php">Voltar ao início</a><a class="btn ghost" href="/casos-reais.php">Ver cases</a></div></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
