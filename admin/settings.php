<?php
require_once __DIR__ . '/../app/bootstrap.php'; require_admin();
$settings=data_read('settings.json',[]); $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 foreach(['city','brand','product','technology','domain','pwa_name','pwa_short_name'] as $k){$settings[$k]=trim((string)($_POST[$k]??''));}
 $msg=data_write('settings.json',$settings)?'Configurações salvas.':'Falha ao salvar.';
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Configurações — Admin</title><link rel="stylesheet" href="/admin/admin.css"></head><body><main class="wrap"><section class="card"><h1>Configurações</h1><nav class="nav"><a href="/admin/index.php">Dashboard</a><a href="/admin/items.php">Conteúdos</a><a href="/admin/logout.php">Sair</a></nav><?php if($msg):?><div class="notice"><?=e($msg)?></div><?php endif;?><form method="post" class="form"><?php foreach(['city','brand','product','technology','domain','pwa_name','pwa_short_name'] as $k):?><label><?=e($k)?><input name="<?=e($k)?>" value="<?=e($settings[$k]??'')?>"></label><?php endforeach;?><button class="btn">Salvar</button></form></section></main></body></html>