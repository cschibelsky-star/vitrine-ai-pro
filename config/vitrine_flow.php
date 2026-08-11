<?php

return [
    'base_url' => env('VITRINE_FLOW_URL', 'https://automacoes.vitrineiapro.com.br'),
    'provision_webhook' => env('VITRINE_FLOW_PROVISION_WEBHOOK', '/webhook/factory/provision'),
    'runtime_webhook' => env('VITRINE_FLOW_RUNTIME_WEBHOOK', '/webhook/flow-runtime'),
    'token' => env('VITRINE_FLOW_TOKEN'),
    'callback_token' => env('VITRINE_FLOW_CALLBACK_TOKEN'),
    'timeout' => (int) env('VITRINE_FLOW_TIMEOUT', 30),

    // Consolidação Core x Factory x Flow.
    // Runtime canônico é preferido; webhook legado permanece como fallback temporário.
    'provision_workflow_key' => env('VITRINE_FLOW_PROVISION_WORKFLOW_KEY', 'provision_product'),
    'prefer_runtime' => filter_var(env('VITRINE_FLOW_PREFER_RUNTIME', true), FILTER_VALIDATE_BOOL),
    'legacy_fallback' => filter_var(env('VITRINE_FLOW_LEGACY_FALLBACK', true), FILTER_VALIDATE_BOOL),
];
