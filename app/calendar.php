<?php
declare(strict_types=1);
function conheca_calendar_timezone(): DateTimeZone { return new DateTimeZone('America/Sao_Paulo'); }
function conheca_calendar_today(?DateTimeImmutable $now = null): string {
    return ($now ?? new DateTimeImmutable('now', conheca_calendar_timezone()))->setTimezone(conheca_calendar_timezone())->format('Y-m-d');
}
function conheca_valid_day($value): bool {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) return false;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, conheca_calendar_timezone());
    return $date !== false && $date->format('Y-m-d') === $value;
}
function conheca_public_url($value): bool {
    if (!is_string($value) || !filter_var($value, FILTER_VALIDATE_URL)) return false;
    $parts = parse_url($value);
    return in_array(strtolower($parts['scheme'] ?? ''), ['http','https'], true) && !isset($parts['user']) && !isset($parts['pass']);
}
// Compatibility is read-only: source records are not rewritten during rendering.
function conheca_normalize_event(array $event): array {
    $aliases = ['title'=>'name','place'=>'location','summary'=>'description','start_date'=>'date_start',
        'end_date'=>'date_end','start_time'=>'time_start','image'=>'image_url'];
    foreach ($aliases as $canonical=>$alias) {
        if (!array_key_exists($canonical, $event)) $event[$canonical] = $event[$alias] ?? '';
        if ($event[$canonical] === null) $event[$canonical] = '';
    }
    if (($event['status'] ?? '') === 'confirmed') {
        $event['status'] = !empty($event['approved_at']) && !empty($event['approved_by']) ? 'published' : 'candidate';
    }
    return $event;
}
function conheca_event_errors(array $raw, ?DateTimeImmutable $now = null): array {
    $event = conheca_normalize_event($raw); $errors = [];
    foreach (['title','place'] as $key) if (!is_string($event[$key]) || trim($event[$key]) === '') $errors[] = 'Informe título e local.';
    if (!conheca_valid_day($event['start_date']) || ($event['end_date'] !== '' && (!conheca_valid_day($event['end_date']) || $event['end_date'] < $event['start_date']))) $errors[] = 'Datas inválidas ou invertidas.';
    if (!conheca_public_url($event['source_url'] ?? null)) $errors[] = 'Informe a fonte pública HTTP(S).';
    if (!conheca_valid_day($event['verified_at'] ?? null) || $event['verified_at'] > conheca_calendar_today($now)) $errors[] = 'Informe a conferência sem data futura.';
    foreach (['summary','start_time','category','source_name','image','address','access','reservation_url','relevance'] as $key) {
        if (isset($event[$key]) && !is_string($event[$key])) $errors[] = 'Campo textual inválido: '.$key;
    }
    return array_values(array_unique($errors));
}
function conheca_published_events(array $events, ?DateTimeImmutable $now = null): array {
    $today = conheca_calendar_today($now); $published = [];
    foreach ($events as $raw) {
        if (!is_array($raw)) continue;
        $event = conheca_normalize_event($raw);
        if (($event['status'] ?? '') !== 'published' || !empty($event['cancelled_at']) || conheca_event_errors($event, $now)) continue;
        if (($event['end_date'] ?: $event['start_date']) >= $today) $published[] = $event;
    }
    usort($published, static fn(array $a,array $b): int => strcmp($a['start_date'],$b['start_date']));
    return $published;
}
function conheca_event_date_label(array $raw): string {
    $event = conheca_normalize_event($raw); $date = $event['start_date'];
    if (!conheca_valid_day($date)) return 'DATA A CONFIRMAR';
    $months = [1=>'JAN',2=>'FEV',3=>'MAR',4=>'ABR',5=>'MAI',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SET',10=>'OUT',11=>'NOV',12=>'DEZ'];
    return substr($date,8,2).' '.$months[(int)substr($date,5,2)];
}
function conheca_public_event(array $event): array {
    $keys = ['id','title','place','summary','start_date','end_date','start_time','end_time','category','image',
        'source_url','source_name','verified_at','address','access','reservation_url','relevance','free_entry','status'];
    $public=array_intersect_key($event, array_flip($keys));
    foreach (['image','reservation_url'] as $key) if (isset($public[$key]) && !conheca_public_url($public[$key])) unset($public[$key]);
    return $public;
}

