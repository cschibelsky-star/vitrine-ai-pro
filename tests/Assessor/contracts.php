<?php
require __DIR__.'/../../app/Assessor/TrialPolicy.php';
require __DIR__.'/../../app/Assessor/HmlTrialProvider.php';
require __DIR__.'/../../app/Assessor/UnavailableHmlTrialProvider.php';
use App\Assessor\TrialPolicy as P;
$count=0;
function check(bool $ok): void { global $count; if (!$ok) { throw new RuntimeException('assertion '.($count+1)); } $count++; }
function rejects(callable $f): void { try { $f(); } catch (DomainException $e) { check(true); return; } throw new RuntimeException('expected rejection'); }
$id=str_repeat('a',32);
$r=['tenant_id'=>$id,'company_id'=>7,'hostname'=>P::host($id),'environment'=>'hml','route_id'=>'official-route',
'dns_valid'=>true,'tls_valid'=>true,'healthy'=>true,'isolated'=>true,'cockpit_registered'=>true,'access_ready'=>true];
P::receipt($r,$id,7);check(true);
foreach(['dns_valid','tls_valid','healthy','isolated','cockpit_registered','access_ready'] as $g) {
 $bad=$r;$bad[$g]=false;rejects(fn()=>P::receipt($bad,$id,7));
 $bad[$g]='true';rejects(fn()=>P::receipt($bad,$id,7));
}
foreach(['tenant_id'=>'wrong','company_id'=>8,'hostname'=>'evil.test','environment'=>'production','route_id'=>''] as $k=>$v) {
 $bad=$r;$bad[$k]=$v;rejects(fn()=>P::receipt($bad,$id,7));
}
rejects(fn()=>P::host('../admin'));
[$start,$end]=P::dates(new DateTimeImmutable('2026-10-13T19:41:21-03:00'));
check($start->format('c')==='2026-10-13T22:41:21+00:00');
check($end->getTimestamp()-$start->getTimestamp()===15*86400);
check($end->format('c')==='2026-10-28T22:41:21+00:00');
// Calendar boundaries and DST must still yield fifteen complete UTC days.
foreach (['2026-01-31T23:59:59+14:00', '2028-02-20T12:00:00Z', '2026-03-01T12:00:00 America/New_York', '2026-10-25T12:00:00 America/New_York'] as $input) {
 $now=new DateTimeImmutable($input);
 [$from,$until]=P::dates($now);
 check($from->getTimestamp()===$now->getTimestamp());
 check($from->getTimezone()->getName()==='UTC');
 check($until->getTimestamp()-$from->getTimestamp()===15*86400);
 check(!P::accessible('active',$until,$from->modify('+30 days')));
 check(!P::accessible('active',$until,$from->modify('+12 months')));
}
check(P::accessible('active',$end,$end->modify('-1 second')));
check(!P::accessible('active',$end,$end));
check(!P::accessible('active',$end,$end->modify('+1 second')));
foreach(['requested','blocked','cancelled','expired','cancelled_pending','expired_pending','trial','active_prepaid_30d','active_annual','active_site_only','suspended','canceled'] as $s) { check(!P::accessible($s,$end,$start)); }
check(!P::accessible('active',null,$start));
try { (new App\Assessor\UnavailableHmlTrialProvider)->provision($id,7,P::host($id)); throw new LogicException('must block'); }
catch (RuntimeException $e) { check($e->getMessage()==='official_hml_trial_adapter_not_configured'); }
echo "{$count} contract checks passed\n";
