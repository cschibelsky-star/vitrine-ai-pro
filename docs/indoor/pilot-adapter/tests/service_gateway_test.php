<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/IndoorServiceGateway.php';
function assertGate(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE indoor_request_nonces (service_id TEXT NOT NULL, nonce_hash TEXT NOT NULL, expires_at INTEGER NOT NULL, PRIMARY KEY(service_id,nonce_hash))');
$path = '/api/indoor/v1/imports/tvsumare';
$credentials = [
    'tvsumare_hml' => ['secret'=>str_repeat('a',32), 'tenant_id'=>'tenant_tvsumare', 'enabled'=>true, 'allowed_path'=>$path],
    'other_hml' => ['secret'=>str_repeat('b',32), 'tenant_id'=>'tenant_other', 'enabled'=>true, 'allowed_path'=>$path],
    'disabled' => ['secret'=>str_repeat('c',32), 'tenant_id'=>'tenant_disabled', 'enabled'=>false, 'allowed_path'=>$path]
];
$ts = time(); $nonce = str_repeat('e',32);
$body = '{"tenant_id":"tenant_other","action":"preview"}';
$sig = IndoorServiceAuth::signature(str_repeat('a',32),'POST',$path,$body,$ts,$nonce);
$args = [$credentials,$db,'tvsumare_hml','POST',$path,$body,$ts,$nonce,$sig,$ts];
$identity = IndoorServiceGateway::authorize(...$args);
assertGate(($identity['tenant_id'] ?? null)==='tenant_tvsumare','Tenant must come from credentials, not request body');
assertGate(IndoorServiceGateway::authorize(...$args)===null,'Replay rejected');
assertGate(IndoorServiceGateway::authorize($credentials,$db,'other_hml','POST',$path,$body,$ts,$nonce,$sig,$ts)===null,'Cross-service signature rejected');
assertGate(IndoorServiceGateway::authorize($credentials,$db,'disabled','POST',$path,$body,$ts,$nonce,$sig,$ts)===null,'Disabled credentials rejected');
assertGate(IndoorServiceGateway::authorize($credentials,$db,'missing','POST',$path,$body,$ts,$nonce,$sig,$ts)===null,'Unknown service rejected');
assertGate(IndoorServiceGateway::authorize($credentials,$db,'tvsumare_hml','POST','/api/indoor/v1/other',$body,$ts,str_repeat('f',32),$sig,$ts)===null,'Wrong route rejected');
echo "OK: server-owned service identity, tenant scoping and replay gates\n";
