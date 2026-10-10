<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_admin();
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function review_url($value): string {
    $value = trim((string)$value);
    return filter_var($value, FILTER_VALIDATE_URL) && strtolower((string)parse_url($value, PHP_URL_SCHEME)) === 'https' ? $value : '';
}
function review_day($value): bool {
    $value = (string)$value;
    $day = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('America/Sao_Paulo'));
    return $day !== false && $day->format('Y-m-d') === $value;
}
function review_problem(array $event): string {
    foreach (['title', 'place', 'summary'] as $field) {
        if (trim((string)($event[$field] ?? '')) === '') return 'Complete nome, local e resumo antes de publicar.';
    }
    if (!review_day($event['start_date'] ?? '')) return 'A data inicial precisa ser confirmada.';
    $last = trim((string)($event['end_date'] ?? '')) ?: $event['start_date'];
    if (!review_day($last) || $last < $event['start_date']) return 'Confira a data final do evento.';
    if ($last < (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d')) return 'Evento encerrado: publicação bloqueada.';
    if (review_url($event['source_url'] ?? '') === '' || trim((string)($event['source_name'] ?? '')) === '') return 'O evento precisa de uma fonte pública identificada.';
    if (!review_day($event['verified_at'] ?? '')) return 'A data de verificação precisa ser registrada.';
    foreach (['start_time', 'end_time'] as $field) {
        if (!empty($event[$field]) && !preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', (string)$event[$field])) return 'Confira o horário informado.';
    }
    return '';
}
function review_events(): array {
    $path = DATA_DIR . '/events.json';
    if (!is_file($path)) return [];
    $text = file_get_contents($path);
    if ($text === false) throw new RuntimeException('Não foi possível ler a agenda.');
    $events = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($events) || !array_is_list($events)) throw new RuntimeException('Formato da agenda inválido; nenhum dado foi alterado.');
    foreach ($events as $event) if (!is_array($event)) throw new RuntimeException('Registro inválido na agenda; nenhum dado foi alterado.');
    return $events;
}
function review_fingerprint(array $event): string {
    return hash('sha256', json_encode($event, JSON_THROW_ON_ERROR));
}
function review_change(string $id, string $action, string $version): void {
    $lock = fopen(DATA_DIR . '/.events-review.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Agenda ocupada. Tente novamente.');
    $tmp = false;
    try {
        $events = review_events();
        $matches = array_keys(array_filter($events, static fn(array $event): bool => (string)($event['id'] ?? '') === $id));
        if ($id === '' || count($matches) !== 1) throw new RuntimeException('Evento não encontrado ou identificador duplicado.');
        $index = $matches[0];
        $event = $events[$index];
        if (!hash_equals(review_fingerprint($event), $version)) throw new RuntimeException('Este evento mudou. Atualize a página e revise novamente.');
        $status = (string)($event['status'] ?? 'candidate');
        $allowed = ['publish' => ['candidate', 'review'], 'reject' => ['candidate', 'review'], 'restore' => ['rejected', 'discarded', 'published']];
        if (!isset($allowed[$action]) || !in_array($status, $allowed[$action], true)) throw new RuntimeException('Ação inválida para o estado atual do evento.');
        if ($action === 'publish' && ($problem = review_problem($event)) !== '') throw new RuntimeException($problem);
        $event['status'] = ['publish' => 'published', 'reject' => 'rejected', 'restore' => 'candidate'][$action];
        $event['reviewed_at'] = (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format(DateTimeInterface::ATOM);
        $event['reviewed_by'] = (string)($_SESSION['admin_email'] ?? getenv('ADMIN_USER'));
        if ($action === 'publish') { $event['approved_at'] = $event['reviewed_at']; $event['approved_by'] = $event['reviewed_by']; }
        if ($action === 'restore') unset($event['approved_at'], $event['approved_by']);
        $events[$index] = $event;
        $json = json_encode($events, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
        if (!copy(DATA_DIR . '/events.json', DATA_DIR . '/events-backup-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.json')) throw new RuntimeException('Backup falhou; gravação cancelada.');
        $tmp = tempnam(DATA_DIR, '.event-review-');
        if ($tmp === false || file_put_contents($tmp, $json, LOCK_EX) === false) throw new RuntimeException('Falha ao salvar; o registro anterior foi preservado.');
        chmod($tmp, 0664);
        if (!rename($tmp, DATA_DIR . '/events.json')) throw new RuntimeException('Falha ao concluir a gravação.');
        $tmp = false;
    } finally {
        if ($tmp !== false && is_file($tmp)) unlink($tmp);
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

if (empty($_SESSION['event_review_csrf'])) $_SESSION['event_review_csrf'] = bin2hex(random_bytes(32));
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['event_review_csrf'], $_POST['csrf'])) throw new RuntimeException('Sessão expirada. Atualize a página.');
        if (($_POST['action'] ?? '') === 'publish' && empty($_POST['public_checked'])) throw new RuntimeException('Confirme a conferência da fonte pública antes de publicar.');
        review_change((string)($_POST['event_id'] ?? ''), (string)($_POST['action'] ?? ''), (string)($_POST['version'] ?? ''));
        $_SESSION['event_review_notice'] = ($_POST['action'] === 'publish') ? 'Evento aprovado e publicado na agenda.' : 'Estado do evento atualizado.';
        header('Location: /admin/index.php#eventos', true, 303);
        exit;
    } catch (Throwable $exception) { $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'Não foi possível processar a agenda; nenhum registro foi descartado.'; }
}
$notice = (string)($_SESSION['event_review_notice'] ?? '');
unset($_SESSION['event_review_notice']);
$items = data_read('items.json', []);
$events = [];
try { $events = review_events(); } catch (Throwable $exception) { $error = 'Não foi possível carregar a agenda. Os dados existentes foram preservados.'; }
$candidates = count(array_filter($events, static fn(array $event): bool => ($event['status'] ?? 'candidate') === 'candidate'));
$labels = ['candidate' => 'Aguardando revisão', 'published' => 'Publicado', 'rejected' => 'Descartado', 'discarded' => 'Descartado', 'review' => 'Em revisão'];
?><!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Painel — Conheça Sumaré</title><link rel="stylesheet" href="/admin/admin.css">
<style>.event-review{margin-top:24px;border-top:1px solid #dce5df;padding-top:20px}.event-review img{width:100%;max-width:360px;max-height:240px;object-fit:contain;border-radius:12px;background:#eef3ef}.review-details{display:grid;grid-template-columns:minmax(0,360px) minmax(0,1fr);gap:24px}.review-details dl{margin:0}.review-details dt{font-weight:700;margin-top:12px}.review-details dd{margin:4px 0;overflow-wrap:anywhere}.review-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:20px}.review-actions button{padding:12px 18px;border:1px solid #0b6b46;border-radius:8px;cursor:pointer}.review-actions button:disabled{opacity:.5;cursor:not-allowed}.review-status{display:inline-block;background:#edf3ee;border-radius:6px;padding:5px 10px}.review-warning{color:#8b3d00}.review-source{overflow-wrap:anywhere}@media(max-width:720px){.review-details{grid-template-columns:1fr}.review-actions{flex-direction:column}.review-actions button{width:100%}}</style></head>
<body><main class="wrap"><section class="card"><h1>Painel Conheça Sumaré</h1><p class="muted">Revise os anúncios confirmados antes de incluí-los na agenda pública.</p>
<nav class="nav"><a href="/admin/index.php">Dashboard</a><a href="#eventos">Revisar eventos</a><a href="/admin/items.php">Conteúdos</a><a href="/admin/settings.php">Configurações</a><a href="/admin/logout.php">Sair</a></nav>
<?php if ($error): ?><div class="notice warning" role="alert"><?=e($error)?></div><?php endif; ?>
<?php if ($notice): ?><div class="notice" role="status"><?=e($notice)?></div><?php endif; ?>
<div class="grid"><div class="stat"><strong><?=count($items)?></strong><p>conteúdos</p></div><div class="stat"><strong><?=count($events)?></strong><p>eventos cadastrados</p></div><div class="stat"><strong><?=$candidates?></strong><p>aguardando revisão</p></div><div class="stat"><strong>PWA</strong><p>Guia Digital da Cidade</p></div></div>
</section><section class="card" id="eventos" style="margin-top:24px"><h2>Revisão de eventos</h2><p>Candidatos só entram na agenda após sua aprovação. Confira a fonte e as condições de participação.</p>
<?php if (!$events): ?><p>Nenhum evento cadastrado.</p><?php endif; ?>
<?php foreach ($events as $event): $status = (string)($event['status'] ?? 'candidate'); $problem = review_problem($event); $source = review_url($event['source_url'] ?? ''); $image = review_url($event['image'] ?? ''); ?>
<article class="event-review"><span class="review-status"><?=e($labels[$status] ?? 'Estado desconhecido')?></span><h3><?=e($event['title'] ?? 'Evento sem nome')?></h3><div class="review-details">
<div><?php if ($image): ?><img src="<?=e($image)?>" alt="<?=e($event['title'] ?? '')?>" loading="lazy" referrerpolicy="no-referrer"><?php endif; ?><p><?=e($event['summary'] ?? '')?></p><?php if (!empty($event['relevance'])): ?><p><b>Relevância para o guia:</b> <?=e($event['relevance'])?></p><?php endif; ?></div>
<dl><dt>Categoria</dt><dd><?=e($event['category'] ?? 'A confirmar')?></dd><dt>Data e horário</dt><dd><?=e($event['start_date'] ?? $event['date'] ?? 'A confirmar')?><?php if (!empty($event['end_date']) && $event['end_date'] !== ($event['start_date'] ?? '')): ?> a <?=e($event['end_date'])?><?php endif; ?><?php if (empty($event['start_time']) && !empty($event['time'])): ?> · <?=e($event['time'])?><?php endif; ?><?php if (!empty($event['start_time'])): ?> · <?=e($event['start_time'])?><?php endif; ?><?php if (!empty($event['end_time'])): ?> às <?=e($event['end_time'])?><?php endif; ?></dd><dt>Local</dt><dd><?=e($event['place'] ?? 'A confirmar')?><br><?=e($event['address'] ?? '')?></dd><dt>Participação</dt><dd><?=e($event['access'] ?? 'Confira as condições na fonte')?></dd><dt>Fonte pública</dt><dd class="review-source"><?php if ($source): ?><a href="<?=e($source)?>" target="_blank" rel="noopener noreferrer"><?=e($event['source_name'] ?? 'Consultar anúncio')?></a><?php else: ?>A confirmar<?php endif; ?></dd><dt>Anúncio e verificação</dt><dd>Publicado em <?=e($event['source_published_date'] ?? $event['announced_at'] ?? 'não informado')?> · Verificado em <?=e($event['verified_at'] ?? 'não informado')?></dd></dl></div>
<?php if (in_array($status, ['candidate', 'review'], true) && $problem): ?><p class="review-warning"><?=e($problem)?></p><?php endif; ?>
<form method="post" class="review-actions"><input type="hidden" name="csrf" value="<?=e($_SESSION['event_review_csrf'])?>"><input type="hidden" name="event_id" value="<?=e((string)($event['id'] ?? ''))?>"><input type="hidden" name="version" value="<?=e(review_fingerprint($event))?>">
<?php if (in_array($status, ['candidate', 'review'], true)): ?><label><input type="checkbox" name="public_checked" value="1" required> Conferi a edição, a data, o local e a fonte pública.</label><button class="btn" name="action" value="publish" <?=$problem !== '' ? 'disabled' : ''?> onclick="return confirm('Aprovar este evento e publicá-lo na agenda do Conheça Sumaré?')">Aprovar e publicar</button><button name="action" value="reject" formnovalidate onclick="return confirm('Descartar este candidato? O registro será preservado.')">Descartar candidato</button>
<?php elseif (in_array($status, ['published', 'rejected', 'discarded'], true)): ?><button name="action" value="restore"><?= $status === 'published' ? 'Retirar da agenda e voltar à revisão' : 'Voltar à revisão' ?></button><?php endif; ?></form></article>
<?php endforeach; ?></section></main></body></html>
