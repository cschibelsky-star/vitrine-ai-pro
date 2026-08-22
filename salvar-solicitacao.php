<?php
require_once __DIR__.'/includes/functions.php';
if(session_status() !== PHP_SESSION_ACTIVE){ session_start(); }
if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()){ form_error_redirect(); }

$data=[
  'perfil'=>clean_input($_POST['perfil'] ?? '',255),
  'organizacao'=>clean_input($_POST['organizacao'] ?? '',255),
  'nome'=>clean_input($_POST['nome'] ?? '',255),
  'cargo'=>clean_input($_POST['cargo'] ?? '',255),
  'email'=>clean_input($_POST['email'] ?? '',255),
  'telefone'=>clean_input($_POST['telefone'] ?? '',50),
  'cidade'=>clean_input($_POST['cidade'] ?? '',255),
  'modelo'=>clean_input($_POST['modelo'] ?? '',255),
  'modelo_comercial'=>clean_input($_POST['modelo_comercial'] ?? 'Ainda não sei',255),
  'finalidade'=>clean_input($_POST['finalidade'] ?? '',255),
  'necessidade'=>clean_input($_POST['necessidade'] ?? '',4000),
  'consentimento_lgpd'=>!empty($_POST['consentimento_lgpd']) ? '1' : '',
  'pagina_origem'=>'/solicitacao-institucional.php',
];

if($data['perfil']==='' || $data['organizacao']==='' || $data['nome']==='' || $data['email']==='' || $data['telefone']==='' || $data['modelo']==='' || $data['necessidade']==='' || $data['consentimento_lgpd']!=='1'){
  form_error_redirect();
}
if(!filter_var($data['email'],FILTER_VALIDATE_EMAIL)){ form_error_redirect(); }

$localId=lead_save($data);
$data['external_id']=$localId;
$masterOk=master_lead_send(build_master_lead_payload($data));
$_SESSION['solicitacao_recebida']=[
  'id'=>$localId,
  'nome'=>$data['nome'],
  'modelo'=>$data['modelo'],
  'modelo_comercial'=>$data['modelo_comercial'],
  'master'=>$masterOk ? '1' : '0',
];
header('Location: /solicitacao-recebida.php');
exit;
