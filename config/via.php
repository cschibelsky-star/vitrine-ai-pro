<?php

return [
    'enabled' => env('VIA_ENABLED', false),
    'environment' => env('VIA_ENVIRONMENT', 'homologation'),
    'route_endpoint' => env('VIA_ROUTE_ENDPOINT'),
    'voice_enabled' => env('VIA_VOICE_ENABLED', false),
    'developer_mode' => env('VIA_DEVELOPER_MODE', false),
];
