<?php
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/event-store.php';
require_admin();
$_SESSION['events_csrf'] ??= bin2hex(random_bytes(32));
$message=''; $error='';
try {
    if ($_SERVER['REQUEST_METHOD']==='POST') {
        if (!hash_equals($_SESSION['events_csrf'],(string)($_POST['csrf'] ?? ''))) { http_response_code(403); throw new RuntimeException('Sessão expirada. Recarregue a página.'); }
        if (($_POST['action'] ?? '')==='import') {
            $json=(string)($_POST['events_json'] ?? '');
            if (strlen($json)>1000000) throw new RuntimeException('Importação excede 1 MB.');
            $incoming=json_decode($json,true,512,JSON_THROW_ON_ERROR);
            if (!is_array($incoming)) throw new RuntimeException('Informe uma lista JSON.');
            $origin=trim((string)($_POST['origin'] ?? ''));
            if ($origin==='') throw new RuntimeException('Origem obrigatória para deduplicação.');
            conheca_mutate_events(fn($events)=>conheca_import_candidates($events,$incoming,$origin));
            $message='Candidatos importados. Confira cada registro antes de aprovar.';
        } else {
            if (($_POST['action'] ?? '')==='approve' && empty($_POST['public_checked'])) throw new RuntimeException('Confirme a conferência pública antes de aprovar.');
            conheca_mutate_events(fn($events)=>conheca_review_event($events,(string)($_POST['id'] ?? ''),(string)($_POST['action'] ?? ''),$_POST,(string)getenv('ADMIN_USER')));
            $message='Revisão salva. Homepage, agenda e feed usam a mesma base.';
        }
    }
    $events=conheca_read_events();
} catch (Throwable $e) { $error=$e->getMessage(); $events=[]; try { $events=conheca_read_events(); } catch (Throwable $ignored) {} }
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Curadoria de eventos — Conheça Sumaré</title><link rel="stylesheet" href="/admin/admin.css"></head>
<body><main class="wrap"><section class="card"><h1>Curadoria de eventos</h1><nav class="nav"><a href="/admin/index.php">Painel</a><a href="/eventos">Ver agenda</a></nav>
<p>Confira edição, data, local, fonte pública e eventual cancelamento. Aprovar publica na homepage e no PWA.</p>
<?php if($message): ?><p role="status"><?=e($message)?></p><?php endif; ?><?php if($error): ?><p role="alert"><?=e($error)?></p><?php endif; ?>
<details><summary>Importar candidatos do React ou do radar</summary><form method="post" class="form">
<input type="hidden" name="csrf" value="<?=e($_SESSION['events_csrf'])?>"><input type="hidden" name="action" value="import">
<label>Origem estável<input name="origin" value="visite-sumare" required></label>
<label>Lista JSON<textarea name="events_json" rows="8" required></textarea></label><button class="btn">Importar para conferência</button></form></details></section>
<?php foreach($events as $raw): if(!is_array($raw)) continue; $event=conheca_normalize_event($raw); ?>
<section class="card"><h2><?=e($event['title'])?></h2><p>Estado: <?=e($event['status'] ?? 'candidate')?> · ID: <?=e((string)($event['id'] ?? ''))?></p>
<form method="post" class="form"><input type="hidden" name="csrf" value="<?=e($_SESSION['events_csrf'])?>"><input type="hidden" name="id" value="<?=e((string)($event['id'] ?? ''))?>">
<?php foreach(['title'=>'Título','place'=>'Local','start_date'=>'Data inicial (AAAA-MM-DD)','end_date'=>'Data final inclusiva (opcional)','start_time'=>'Horário','category'=>'Categoria','source_url'=>'Link da confirmação pública','source_name'=>'Nome da fonte','verified_at'=>'Última conferência (AAAA-MM-DD)','summary'=>'Resumo'] as $key=>$label): ?>
<label><?=e($label)?><input name="<?=e($key)?>" value="<?=e((string)($event[$key] ?? ''))?>"></label><?php endforeach; ?>
<label><input type="checkbox" name="public_checked" required> Conferi a fonte, as datas, o local e o estado do evento.</label>
<button class="btn" name="action" value="approve">Aprovar e publicar</button><button name="action" value="cancel" formnovalidate>Cancelar evento</button><button name="action" value="reject" formnovalidate>Rejeitar</button></form></section>
<?php endforeach; ?></main></body></html>
