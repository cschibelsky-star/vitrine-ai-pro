<?php
return [
  'site_name' => 'VITRINE IA PRO',
  'tagline' => 'Soluções digitais escaláveis em modelo SaaS, personalizado e White Label.',
  'base_url' => '',
  'contact_email' => getenv('VITRINE_CONTACT_EMAIL') ?: '',
  'whatsapp' => getenv('VITRINE_WHATSAPP') ?: '',
  'admin_user' => getenv('VITRINE_ADMIN_USER') ?: '',
  'admin_pass_hash' => getenv('VITRINE_ADMIN_PASS_HASH') ?: '',
  'demo_notice' => 'Cenário de referência fictício para avaliação comercial da VITRINE IA PRO. Nomes, dados e conteúdos não representam órgão público, empresa, veículo de comunicação ou cidade real.',
  'master_leads_api' => getenv('VITRINE_MASTER_LEADS_API') ?: 'https://app.vitrineiapro.com.br/api/leads'
];
