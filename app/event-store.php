<?php
declare(strict_types=1);
require_once __DIR__.'/calendar.php';
function conheca_events_directory(): string { return getenv('EVENTS_DATA_DIR') ?: dirname(__DIR__).'/storage/data'; }
function conheca_read_events(): array {
    $path = conheca_events_directory().'/events.json';
    if (!is_file($path)) return [];
    $data = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !array_is_list($data)) throw new RuntimeException('Formato inválido da agenda; nenhum dado foi alterado.');
    return $data;
}
function conheca_mutate_events(callable $mutation): array {
    $dir = conheca_events_directory();
    if (!is_dir($dir) && !mkdir($dir,0770,true)) throw new RuntimeException('Não foi possível criar a agenda.');
    $lock = fopen($dir.'/events.lock','c');
    if (!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Agenda ocupada.');
    $tmp = null;
    try {
        $before = conheca_read_events(); $after = $mutation($before);
        if (!is_array($after) || !array_is_list($after)) throw new RuntimeException('Operação inválida.');
        if ($after === $before) return $before;
        $path = $dir.'/events.json'; $suffix = gmdate('YmdHis').'-'.bin2hex(random_bytes(6));
        if (is_file($path) && !copy($path,$dir.'/events-backup-'.$suffix.'.json')) throw new RuntimeException('Backup falhou; gravação cancelada.');
        $tmp = tempnam($dir,'events-tmp-');
        if ($tmp === false || file_put_contents($tmp,json_encode($after,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)."\n",LOCK_EX) === false || !rename($tmp,$path)) throw new RuntimeException('Falha na gravação da agenda.');
        return $after;
    } finally { if ($tmp && is_file($tmp)) unlink($tmp); flock($lock,LOCK_UN); fclose($lock); }
}
function conheca_import_candidates(array $events, array $incoming, string $origin): array {
    if (!array_is_list($incoming) || count($incoming)>200) throw new RuntimeException('Importe uma lista de até 200 eventos.');
    foreach ($incoming as $raw) {
        if (!is_array($raw) || !is_scalar($raw['id'] ?? null)) throw new RuntimeException('Evento sem ID estável.');
        $id = 'import:'.hash('sha256',$origin.':'.(string)$raw['id']);
        if (array_filter($events,static fn($e)=>is_array($e) && (string)($e['id'] ?? '') === $id)) continue;
        $event = conheca_normalize_event($raw);
        $event['id']=$id; $event['upstream_id']=$raw['id']; $event['upstream_origin']=$origin;
        $event['upstream_status']=$raw['status'] ?? 'candidate'; $event['status']='candidate';
        unset($event['approved_at'],$event['approved_by']);
        $event['imported_at']=gmdate(DATE_ATOM); $event['revision']=1;
        $events[]=$event;
    }
    return $events;
}
function conheca_review_event(array $events, string $id, string $action, array $fields, string $reviewer): array {
    foreach ($events as $i=>$raw) {
        if (!is_array($raw) || (string)($raw['id'] ?? '') !== $id) continue;
        $event = conheca_normalize_event($raw);
        if ($action === 'approve') {
            foreach (['title','place','summary','start_date','end_date','start_time','source_url','source_name','verified_at','category'] as $key) {
                if (isset($fields[$key])) $event[$key]=trim((string)$fields[$key]);
            }
            $errors=conheca_event_errors($event);
            if ($errors) throw new RuntimeException(implode(' ',$errors));
            if ($reviewer==='') throw new RuntimeException('Revisor obrigatório.');
            $event['status']='published'; $event['approved_at']=gmdate(DATE_ATOM); $event['approved_by']=$reviewer;
            unset($event['cancelled_at']);
        } elseif ($action === 'cancel') { $event['status']='cancelled'; $event['cancelled_at']=gmdate(DATE_ATOM); }
        elseif ($action === 'reject') { $event['status']='rejected'; }
        else throw new RuntimeException('Ação inválida.');
        $event['revision']=(int)($event['revision'] ?? 0)+1;
        $event['reviewed_at']=gmdate(DATE_ATOM); $event['reviewed_by']=$reviewer;
        $events[$i]=$event; return $events;
    }
    throw new RuntimeException('Evento não encontrado.');
}
