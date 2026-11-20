<?php
declare(strict_types=1);
$root=dirname(__DIR__,2).'/app/Factory/Observer/';
foreach(['AuthorizedMissionScope','FactoryReadOnlyProvider','FactoryObserver','MissionObserverGateway'] as $name) require $root.$name.'.php';

use App\Factory\Observer\AuthorizedMissionScope;
use App\Factory\Observer\MissionObserverGateway;

$checks=0;
function verify(string $name, callable $fn): void { global $checks; $fn(); $checks++; echo "PASS: $name\n"; }
function denied(callable $fn): void { try { $fn(); } catch (\RuntimeException $e) { return; } throw new \RuntimeException('Expected denial'); }
$approved = new AuthorizedMissionScope('mis_1','tenant_1','actor_1',[],['factory.health'],true);
$unapproved = new AuthorizedMissionScope('mis_2','tenant_1','actor_1',[],['factory.health'],false);
$audit=[];
$gateway=new MissionObserverGateway(fn($s,$c,$p)=>true, function($event) use (&$audit) { $audit[]=$event; });
verify('approved health', function() use($gateway,$approved) { $r=$gateway->inspect($approved,'factory.health'); if($r['mode']!=='READ_ONLY') throw new \RuntimeException('Mode'); });
verify('audit contains mission and tenant', function() use(&$audit) { if(count($audit)!==1 || $audit[0]['tenant_id']!=='tenant_1' || $audit[0]['mission_id']!=='mis_1') throw new \RuntimeException('Audit'); });
verify('unapproved mission denied', fn()=>denied(fn()=> $gateway->inspect($unapproved,'factory.health')));
verify('missing capability denied', fn()=>denied(fn()=> $gateway->inspect($approved,'factory.project.inspect',['project_id'=>'p1'])));
verify('project without scope denied', function() { $s=new AuthorizedMissionScope('mis_1','tenant_1','actor_1',[],['factory.project.inspect'],true); denied(fn()=> (new MissionObserverGateway(fn()=>true,fn()=>null))->inspect($s,'factory.project.inspect',['project_id'=>'p1'])); });
verify('policy callback denial', fn()=>denied(fn()=> (new MissionObserverGateway(fn()=>false,fn()=>null))->inspect($approved,'factory.health')));
verify('audit failure stops read', fn()=>denied(fn()=> (new MissionObserverGateway(fn()=>true,fn()=>throw new \RuntimeException('audit down')))->inspect($approved,'factory.health')));
verify('deploy capability rejected', fn()=>denied(fn()=>new AuthorizedMissionScope('mis_1','tenant_1','actor_1',[],['factory.deploy'],true)));
verify('invalid tenant identity rejected', fn()=>denied(fn()=>new AuthorizedMissionScope('mis_1','../tenant','actor_1',[],['factory.health'],true)));
verify('missing audit callback rejected', fn()=>denied(fn()=>new MissionObserverGateway(fn()=>true,null)));
echo "$checks mission gateway checks passed\n";
