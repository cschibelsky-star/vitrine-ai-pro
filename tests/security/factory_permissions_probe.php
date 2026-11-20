<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/Factory/Policies/Concerns/ChecksFactoryPermissions.php';
final class Probe { use \App\Factory\Policies\Concerns\ChecksFactoryPermissions; public function check($u, string $p): bool { return $this->allowed($u,$p); } }
final class PermUser { public function __construct(private bool $grant, public string $role='client', public bool $is_active=true) {} public function hasPermissionTo(string $p): bool { return $this->grant; } }
$p = new Probe();
$cases = [
 'anonymous denied'=>[null,'factory.manage',false],
 'missing API denied'=>[(object)[],'factory.manage',false],
 'client denied'=>[(object)['role'=>'client','is_active'=>true],'factory.manage',false],
 'blank legacy role denied'=>[(object)['role'=>'','is_active'=>true],'factory.manage',false],
 'explicit active admin compatible'=>[(object)['role'=>'admin','is_active'=>true],'factory.manage',true],
 'inactive admin denied'=>[(object)['role'=>'admin','is_active'=>false],'factory.manage',false],
 'explicit grant'=>[new PermUser(true),'factory.manage',true],
 'explicit deny overrides admin'=>[new PermUser(false,'admin'),'factory.manage',false],
 'inactive permission holder denied'=>[new PermUser(true,'client',false),'factory.manage',false],
 'non-factory permission denied'=>[(object)['role'=>'admin','is_active'=>true],'other.manage',false],
];
$failures=0;
foreach($cases as $name=>[$user,$perm,$expected]) { $actual=$p->check($user,$perm); $pass=$actual===$expected; echo ($pass?'PASS':'FAIL').": $name\n"; if(!$pass) $failures++; }
exit($failures?1:0);
