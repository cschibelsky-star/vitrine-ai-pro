<?php
declare(strict_types=1);
$root = sys_get_temp_dir().'/conheca-review-'.bin2hex(random_bytes(6));
mkdir($root, 0775, true);
define('DATA_DIR', $root);
$_SESSION = ['admin_email' => 'reviewer@example.test'];
$source = file_get_contents(__DIR__.'/../admin/index.php');
$start = strpos($source, 'function review_url');
$end = strpos($source, 'if (empty(');
if ($start === false || $end === false) throw new RuntimeException('Review functions missing');
eval(substr($source, $start, $end - $start));
$count = 0;
function check(bool $condition, string $message): void {
    global $count;
    if (!$condition) throw new RuntimeException($message);
    $count++;
}
function blocked(callable $action, string $message): void {
    try { $action(); } catch (Throwable $exception) { check(true, $message); return; }
    throw new RuntimeException($message);
}
$base = ['id'=>4, 'title'=>'Future public event', 'category'=>'Cultura', 'place'=>'Sumaré',
    'summary'=>'Confirmed local event', 'start_date'=>'2099-10-17', 'end_date'=>'2099-10-17',
    'source_name'=>'Organizer', 'source_url'=>'https://example.test/event',
    'verified_at'=>'2026-10-09', 'status'=>'candidate', 'start_time'=>'09:00'];
$legacy = ['id'=>1, 'title'=>'Legacy record', 'summary'=>'Keep this unchanged'];
function save_events(array $events): void { file_put_contents(DATA_DIR.'/events.json', json_encode($events)); }
try {
    check(review_problem($base)==='', 'Valid candidate rejected');
    check(review_problem($legacy)!=='', 'Incomplete legacy event can publish');
    check(review_problem(array_replace($base,['start_date'=>'2026-02-30']))!=='', 'Invalid date accepted');
    check(review_problem(array_replace($base,['end_date'=>'2000-01-01']))!=='', 'End before start accepted');
    check(review_problem(array_replace($base,['start_date'=>'2000-01-01','end_date'=>'2000-01-01']))!=='', 'Past event accepted');
    check(review_problem(array_replace($base,['source_url'=>'javascript:alert(1)']))!=='', 'Unsafe source accepted');
    check(review_problem(array_replace($base,['start_time'=>'25:90']))!=='', 'Invalid time accepted');
    save_events([$legacy,$base]);
    blocked(fn()=>review_change('4','publish','old-version'), 'Stale review allowed');
    blocked(fn()=>review_change('4','unknown',review_fingerprint($base)), 'Unknown action allowed');
    review_change('4','publish',review_fingerprint($base));
    $events=review_events();
    check($events[0]===$legacy,'Other records modified');
    check($events[1]['status']==='published','Publication failed');
    check($events[1]['reviewed_by']==='reviewer@example.test','Review audit missing');
    blocked(fn()=>review_change('4','publish',review_fingerprint($events[1])), 'Published event republished');
    review_change('4','restore',review_fingerprint($events[1]));
    $event=review_events()[1];
    check($event['status']==='candidate','Unpublish failed');
    review_change('4','reject',review_fingerprint($event));
    $event=review_events()[1];
    check($event['status']==='rejected','Reject failed');
    review_change('4','restore',review_fingerprint($event));
    check(review_events()[1]['status']==='candidate','Restore rejected failed');
    save_events([$base,$base]);
    blocked(fn()=>review_change('4','publish',review_fingerprint($base)), 'Duplicate ID allowed');
    file_put_contents(DATA_DIR.'/events.json','BROKEN JSON');
    blocked(fn()=>review_change('4','publish',review_fingerprint($base)), 'Corrupt JSON overwritten');
    check(file_get_contents(DATA_DIR.'/events.json')==='BROKEN JSON','Corrupt source discarded');
    $candidate = array_replace($base, ['status'=>'review']);
    save_events([$legacy,$candidate]);
    review_change('4','publish',review_fingerprint($candidate));
    check(review_events()[1]['status']==='published', 'Reviewed candidate cannot publish');
    check(isset(review_events()[1]['approved_at'],review_events()[1]['approved_by']), 'Approval audit missing');
    check(count(glob(DATA_DIR.'/events-backup-*.json'))>0, 'Backup missing');
    echo "OK: $count checks\n";
} finally {
    foreach (glob($root.'/*') as $file) unlink($file);
    foreach (glob($root.'/.*') as $file) if (is_file($file)) unlink($file);
    rmdir($root);
}
