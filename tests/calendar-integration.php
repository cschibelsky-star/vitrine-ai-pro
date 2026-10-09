<?php
declare(strict_types=1);
require_once __DIR__.'/../app/event-store.php';
$dir=sys_get_temp_dir().'/sumare-calendar-'.bin2hex(random_bytes(6)); mkdir($dir);
putenv('EVENTS_DATA_DIR='.$dir);
$checks=0;
function verify(bool $condition,string $label): void { global $checks; $checks++; if(!$condition) throw new RuntimeException($label); }
function render(string $script,string $uri): string {
    $code='$_SERVER["REQUEST_METHOD"]="GET"; $_SERVER["REQUEST_URI"]='.var_export($uri,true).'; include '.var_export(dirname(__DIR__).'/'.$script,true).';';
    $process=proc_open([PHP_BINARY,'-r',$code],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
    $out=stream_get_contents($pipes[1]); $error=stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    verify(proc_close($process)===0,'Rendering failed: '.$error); verify($error==='','Rendering warning: '.$error);
    return $out;
}
try {
    $original=[['id'=>1,'title'=>'Legacy placeholder','date'=>'Permanente']];
    file_put_contents($dir.'/events.json',json_encode($original));
    $react=[['id'=>'external-123','name'=>'TESTE TEMPORÁRIO Festival','location'=>'Praça de teste','date_start'=>'2099-10-10','date_end'=>'2099-10-11','source_url'=>'https://cultura.sumare.sp.gov.br/eventos','verified_at'=>conheca_calendar_today(),'status'=>'confirmed','admin_notes'=>'PRIVATE_MARKER']];
    $events=conheca_mutate_events(fn($e)=>conheca_import_candidates($e,$react,'visite-sumare'));
    verify($events[0]===$original[0],'Legacy row unchanged');
    verify(count($events)===2,'Candidate added without overwrite');
    conheca_mutate_events(fn($e)=>conheca_import_candidates($e,$react,'visite-sumare'));
    verify(count(conheca_read_events())===2,'Repeated import idempotent');
    verify(conheca_published_events($events)===[],'Imported confirmation does not bypass admin');
    verify(!str_contains(render('index.php','/'),'TESTE TEMPORÁRIO Festival'),'Candidate hidden on homepage');
    $id=$events[1]['id'];
    $events=conheca_mutate_events(fn($e)=>conheca_review_event($e,$id,'approve',[],'test-reviewer'));
    verify($events[1]['approved_by']==='test-reviewer','Administrative receipt');
    foreach (['/','/eventos','/?source=pwa'] as $uri) {
        $html=render('index.php',$uri);
        verify(str_contains($html,'TESTE TEMPORÁRIO Festival'),'Approved event rendered: '.$uri);
        verify(str_contains($html,'https://cultura.sumare.sp.gov.br/eventos'),'Public source rendered');
        verify(!str_contains($html,'PRIVATE_MARKER'),'Private notes absent from HTML');
    }
    $feed=json_decode(render('calendar-feed.php','/calendar-feed.php'),true,512,JSON_THROW_ON_ERROR);
    verify(count($feed)===1 && $feed[0]['id']===$id,'Same public feed');
    verify(!isset($feed[0]['admin_notes']),'Private notes absent from feed');
    conheca_mutate_events(fn($e)=>conheca_review_event($e,$id,'cancel',[],'test-reviewer'));
    verify(!str_contains(render('index.php','/eventos'),'TESTE TEMPORÁRIO Festival'),'Cancellation removes event');
    verify(json_decode(render('calendar-feed.php','/calendar-feed.php'),true)===[],'Cancellation removes feed item');
    verify(count(conheca_read_events())===2,'Cancelled and legacy data preserved');
    verify(count(glob($dir.'/events-backup-*.json'))===3,'Every write has a backup; no-op import does not');
    $before=file_get_contents($dir.'/events.json');
    try { conheca_mutate_events(fn($e)=>conheca_review_event($e,$id,'approve',['source_url'=>'javascript:bad'],'test-reviewer')); throw new LogicException('Invalid approval accepted'); }
    catch (RuntimeException $expected) {}
    verify(file_get_contents($dir.'/events.json')===$before,'Failed review leaves data unchanged');
    echo "OK: $checks integration checks\n";
} finally { foreach(glob($dir.'/*') as $file) unlink($file); rmdir($dir); }
