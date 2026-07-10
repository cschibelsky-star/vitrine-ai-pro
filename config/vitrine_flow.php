<?php

return [
    'base_url' => env('VITRINE_FLOW_URL', 'https://automacoes.vitrineiapro.com.br'),
    'provision_webhook' => env('VITRINE_FLOW_PROVISION_WEBHOOK', '/webhook/factory/provision'),
    'token' => env('VITRINE_FLOW_TOKEN'),
    'callback_token' => env('VITRINE_FLOW_CALLBACK_TOKEN'),
    'timeout' => (int) env('VITRINE_FLOW_TIMEOUT', 30),
];
