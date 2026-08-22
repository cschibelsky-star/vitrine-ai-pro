<?php
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/lead-persistence.php';
if(session_status() !== PHP_SESSION_ACTIVE){ session_start(); }
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()){ form_error_redirect(); }

$data=[
  'perfil'=>clean_input($_POST['perfil'] ?? '',255),
  'cidade'=>clean_input($_POST['cidade'] ?? '',255),
  'nome'=>clean_input($_POST['nome'] ?? '',255),
  'email'=>clean_input($_POST['email'] ?? '',255),
  'telefone'=>clean_input($_POST['telefone'] ?? '',50),
  'modelo_comercial'=>clean_input($_POST['modelo_comercial'] ?? '',255),
  'necessidade'=>clean_input($_POST['necessidade'] ?? '',4000),
  'consentimento_lgpd'=>!empty($_POST['consentimento_lgpd']) ? '1' : '',
  'pagina_origem'=>clean_input($_SERVER['HTTP_REFERER'] ?? '/',255),
  'origem'=>'Consultor Digital',
];

if($data['perfil']==='' || $data['nome']==='' || $data['email']==='' || $data['telefone']==='' || $data['necessidade']==='' || $data['consentimento_lgpd']!=='1'){
  form_error_redirect();
}
if(!filter_var($data['email'],FILTER_VALIDATE_EMAIL)){ form_error_redirect(); }

$localId=lead_save_confirmed($data);
if($localId===false){ form_error_redirect(); }
$data['external_id']=$localId;
$masterOk=master_lead_send(build_master_lead_payload($data));
$_SESSION['solicitacao_recebida']=[
  'id'=>$localId,
  'nome'=>$data['nome'],
  'modelo'=>'Direcionamento digital',
  'master'=>$masterOk ? '1' : '0',
];
header('Location: /solicitacao-recebida.php');
exit;
