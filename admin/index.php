<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();

date_default_timezone_set('America/Sao_Paulo');
$items = data_read('items.json', []);
$events = data_read('events.json', []);
$_SESSION['events_csrf'] ??= bin2hex(random_bytes(24));
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_action'], $_POST['event_id'])) {
    $token = (string)($_POST['csrf'] ?? '');
    if (!hash_equals((string)$_SESSION['events_csrf'], $token)) {
        $notice = 'Ação recusada: sessão inválida. Atualize a página e tente novamente.';
    } else {
        $eventId = (string)$_POST['event_id'];
        $action = (string)$_POST['event_action'];
        $targetStatus = [
            'publish' => 'published',
            'discard' => 'discarded',
            'review' => 'review',
            'candidate' => 'candidate',
        ][$action] ?? '';

        if ($targetStatus !== '') {
            $changed = false;
            foreach ($events as &$event) {
                if ((string)($event['id'] ?? '') !== $eventId) continue;
                $event['status'] = $targetStatus;
                $event['updated_at'] = date(DATE_ATOM);
                $changed = true;
                break;
            }
            unset($event);

            if ($changed && data_write('events.json', $events)) {
                header('Location: /admin/index.php?event_updated=1#eventos');
                exit;
            }
            $notice = 'Não foi possível salvar a alteração do evento.';
        }
    }
}

if (isset($_GET['event_updated'])) {
    $notice = 'Evento atualizado com sucesso.';
}

$statusCounts = ['candidate'=>0,'review'=>0,'published'=>0,'discarded'=>0];
foreach ($events as $event) {
    $status = (string)($event['status'] ?? 'review');
    if (isset($statusCounts[$status])) $statusCounts[$status]++;
}

$statusLabels = [
    'candidate' => 'Candidato',
    'review' => 'Em revisão',
    'published' => 'Publicado',
    'discarded' => 'Descartado',
];
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Painel — Conheça Sumaré</title>
<link rel="stylesheet" href="/admin/admin.css">
</head>
<body>
<main class="wrap">
<section class="card">
<h1>Painel Conheça Sumaré</h1>
<p class="muted">Gestão do Guia Digital da Cidade.</p>
<nav class="nav">
<a href="/admin/index.php">Dashboard</a>
<a href="#eventos">Eventos</a>
<a href="/admin/items.php">Conteúdos</a>
<a href="/admin/settings.php">Configurações</a>
<a href="/admin/logout.php">Sair</a>
</nav>

<?php if($notice): ?><div class="notice"><?=e($notice)?></div><?php endif; ?>

<div class="grid">
<div class="stat"><strong><?=count($items)?></strong><p>conteúdos</p></div>
<div class="stat"><strong><?=count($events)?></strong><p>eventos cadastrados</p></div>
<div class="stat"><strong><?=$statusCounts['candidate']?></strong><p>candidatos</p></div>
<div class="stat"><strong><?=$statusCounts['published']?></strong><p>publicados</p></div>
</div>
</section>

<section class="card events-card" id="eventos">
<div class="section-head">
<div>
<h2>Eventos</h2>
<p class="muted">Revise candidatos antes da publicação. Apenas eventos com status <strong>Publicado</strong> aparecem na agenda pública.</p>
</div>
<div class="event-summary">
<span class="badge candidate"><?=$statusCounts['candidate']?> candidatos</span>
<span class="badge review"><?=$statusCounts['review']?> em revisão</span>
<span class="badge published"><?=$statusCounts['published']?> publicados</span>
<span class="badge discarded"><?=$statusCounts['discarded']?> descartados</span>
</div>
</div>

<?php if(!$events): ?>
<div class="notice warning">Nenhum evento cadastrado.</div>
<?php else: ?>
<div class="table-wrap">
<table class="table event-table">
<thead><tr><th>Evento</th><th>Data / local</th><th>Fonte</th><th>Status</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach($events as $event):
$status=(string)($event['status'] ?? 'review');
$date=(string)($event['start_date'] ?? '');
$end=(string)($event['end_date'] ?? '');
$dateLabel=$date !== '' ? date('d/m/Y', strtotime($date)) : 'Data pendente';
if ($end !== '' && $end !== $date) $dateLabel .= ' a '.date('d/m/Y', strtotime($end));
?>
<tr>
<td>
<strong><?=e((string)($event['title'] ?? 'Evento sem título'))?></strong>
<?php if(!empty($event['summary'])): ?><small><?=e((string)$event['summary'])?></small><?php endif; ?>
</td>
<td>
<b><?=e($dateLabel)?></b>
<?php if(!empty($event['time'])): ?><small><?=e((string)$event['time'])?></small><?php endif; ?>
<small><?=e((string)($event['place'] ?? ''))?></small>
<?php if(!empty($event['address'])): ?><small><?=e((string)$event['address'])?></small><?php endif; ?>
</td>
<td>
<?php if(!empty($event['source_url'])): ?>
<a href="<?=e((string)$event['source_url'])?>" target="_blank" rel="noopener"><?=e((string)($event['source_name'] ?? 'Fonte'))?> ↗</a>
<?php else: ?>
<span><?=e((string)($event['source_name'] ?? 'Fonte pendente'))?></span>
<?php endif; ?>
<?php if(!empty($event['verified_at'])): ?><small>Verificado em <?=e(date('d/m/Y', strtotime((string)$event['verified_at'])))?></small><?php endif; ?>
</td>
<td><span class="badge <?=e($status)?>"><?=e($statusLabels[$status] ?? $status)?></span></td>
<td>
<div class="event-actions">
<?php if($status !== 'published'): ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e($_SESSION['events_csrf'])?>"><input type="hidden" name="event_id" value="<?=e((string)$event['id'])?>"><button class="btn success" name="event_action" value="publish">Aprovar e publicar</button></form>
<?php else: ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e($_SESSION['events_csrf'])?>"><input type="hidden" name="event_id" value="<?=e((string)$event['id'])?>"><button class="btn secondary" name="event_action" value="review">Retirar da publicação</button></form>
<?php endif; ?>
<?php if($status !== 'discarded'): ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e($_SESSION['events_csrf'])?>"><input type="hidden" name="event_id" value="<?=e((string)$event['id'])?>"><button class="btn danger" name="event_action" value="discard">Descartar</button></form>
<?php else: ?>
<form method="post"><input type="hidden" name="csrf" value="<?=e($_SESSION['events_csrf'])?>"><input type="hidden" name="event_id" value="<?=e((string)$event['id'])?>"><button class="btn secondary" name="event_action" value="candidate">Reabrir</button></form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>
</section>
</main>
</body>
</html>
