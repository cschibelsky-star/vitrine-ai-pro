<?php

return [
    'enabled' => env('VIA_ENABLED', false),

    'environment' => env('VIA_ENVIRONMENT', 'homologacao'),

    'route_endpoint' => env('VIA_ROUTE_ENDPOINT', '/api/flow/ai/route'),

    'voice_enabled' => env('VIA_VOICE_ENABLED', true),

    'developer_mode' => env('VIA_DEVELOPER_MODE', false),
];
