<?php
function lead_save_confirmed($payload){
  $id = lead_save($payload);
  if(!is_string($id) || !preg_match('/^lead_[A-Za-z0-9_\-]+$/', $id)){
    error_log('Falha ao gerar identificador do lead local.');
    return false;
  }
  $path = __DIR__.'/../data/leads/'.$id.'.json';
  if(!is_file($path) || filesize($path) === 0){
    error_log('Falha ao confirmar persistência do lead local.');
    return false;
  }
  return $id;
}
