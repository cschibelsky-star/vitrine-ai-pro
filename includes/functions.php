<?php
function cfg($key){ static $c=null; if($c===null){$c=require __DIR__.'/../config.php';} return $c[$key] ?? null; }
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function current_path(){ return basename($_SERVER['SCRIPT_NAME']); }
function page_title($title=''){ return $title ? e($title).' | '.e(cfg('site_name')) : e(cfg('site_name')); }
function data_json($file){ $p=__DIR__.'/../data/'.$file; return file_exists($p) ? json_decode(file_get_contents($p), true) : []; }
function clean_input($value, $limit=2000){
  $value = trim((string)$value);
  $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
  return function_exists('mb_substr') ? mb_substr($value, 0, $limit, 'UTF-8') : substr($value, 0, $limit);
}
function csrf_token(){
  if(session_status() !== PHP_SESSION_ACTIVE){ session_start(); }
  if(empty($_SESSION['csrf_token'])){ $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
  return $_SESSION['csrf_token'];
}
function csrf_field(){ return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function csrf_verify(){
  if(session_status() !== PHP_SESSION_ACTIVE){ session_start(); }
  $sent = $_POST['csrf_token'] ?? '';
  return is_string($sent) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}
function lead_save($payload){
  $dir=__DIR__.'/../data/leads'; if(!is_dir($dir)) mkdir($dir,0775,true);
  $safe=[];
  foreach($payload as $k=>$v){ $safe[preg_replace('/[^a-zA-Z0-9_\-]/','',$k)] = clean_input($v, 4000); }
  $safe['created_at']=date('c'); $safe['ip']=$_SERVER['REMOTE_ADDR'] ?? '';
  $id='lead_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
  file_put_contents($dir.'/'.$id.'.json', json_encode($safe, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT), LOCK_EX);
  return $id;
}
function form_error_redirect(){ header('Location: /contato.php?erro=1'); exit; }

function lead_product_from_profile($perfil='', $modelo=''){
  $txt = mb_strtolower((string)$perfil.' '.(string)$modelo, 'UTF-8');
  if(strpos($txt,'tv')!==false || strpos($txt,'audiovisual')!==false){ return ['TV Digital Enterprise','Enterprise']; }
  if(strpos($txt,'notícia')!==false || strpos($txt,'noticias')!==false || strpos($txt,'rádio')!==false || strpos($txt,'radio')!==false || strpos($txt,'jornal')!==false || strpos($txt,'mídia')!==false || strpos($txt,'midia')!==false || strpos($txt,'portal')!==false){ return ['Portal News AI','Pro']; }
  if(strpos($txt,'cidade')!==false || strpos($txt,'turismo')!==false || strpos($txt,'guia')!==false || strpos($txt,'visite')!==false){ return ['Visite Cidade','Governo']; }
  if(strpos($txt,'câmara')!==false || strpos($txt,'camara')!==false || strpos($txt,'prefeitura')!==false || strpos($txt,'secretaria')!==false || strpos($txt,'órgão')!==false || strpos($txt,'orgao')!==false || strpos($txt,'governo')!==false || strpos($txt,'público')!==false || strpos($txt,'publico')!==false){ return ['Município Digital IA','Governo']; }
  return ['Sob análise','Sob proposta'];
}
function master_lead_send($payload){
  $endpoint = cfg('master_leads_api') ?: 'https://app.vitrineiapro.com.br/api/leads';
  $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
  if(!$json){ return false; }

  if(function_exists('curl_init')){
    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
      CURLOPT_POSTFIELDS => $json,
      CURLOPT_TIMEOUT => 8,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if($status >= 200 && $status < 300){ return true; }
    error_log('Falha ao enviar lead ao Master. HTTP '.$status.' '.$err.' Resposta: '.$response);
    return false;
  }

  $ctx = stream_context_create([
    'http' => [
      'method' => 'POST',
      'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
      'content' => $json,
      'timeout' => 8,
      'ignore_errors' => true,
    ],
  ]);
  $response = @file_get_contents($endpoint, false, $ctx);
  $statusLine = $http_response_header[0] ?? '';
  if(preg_match('/\s(20\d)\s/', $statusLine)){ return true; }
  error_log('Falha ao enviar lead ao Master. '.$statusLine.' Resposta: '.$response);
  return false;
}
function build_master_lead_payload($data){
  [$produto, $plano] = lead_product_from_profile($data['perfil'] ?? '', $data['modelo'] ?? '');
  $nome = $data['nome'] ?? ($data['contato'] ?? '');
  $empresa = $data['organizacao'] ?? ($data['empresa'] ?? ($data['cidade'] ?? 'Lead do site'));
  $observacoes = [];
  foreach(['perfil','cargo','modelo','finalidade','necessidade','cidade','origem'] as $k){
    if(!empty($data[$k])){ $observacoes[] = ucfirst(str_replace('_',' ', $k)).': '.$data[$k]; }
  }
  return [
    'empresa' => clean_input($empresa, 255),
    'contato' => clean_input($nome, 255),
    'telefone' => clean_input($data['telefone'] ?? '', 50),
    'email' => clean_input($data['email'] ?? '', 255),
    'cidade' => clean_input($data['cidade'] ?? '', 255),
    'estado' => '',
    'produto_interesse' => $produto,
    'plano_sugerido' => $plano,
    'origem_lead' => 'Site',
    'observacoes' => clean_input(implode("\n", $observacoes), 4000),
  ];
}

