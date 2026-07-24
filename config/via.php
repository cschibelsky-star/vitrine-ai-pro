<?php

return [
    'enabled' => env('VIA_ENABLED', false),
    'environment' => env('VIA_ENVIRONMENT', 'homologation'),

    'agent_id' => env('VIA_AGENT_ID'),
    'agent_slug' => env('VIA_AGENT_SLUG', 'via-assistente'),
    'history_limit' => (int) env('VIA_HISTORY_LIMIT', 20),

    'route_endpoint' => env('VIA_ROUTE_ENDPOINT'),
    'voice_enabled' => env('VIA_VOICE_ENABLED', false),
    'developer_mode' => env('VIA_DEVELOPER_MODE', false),
];
