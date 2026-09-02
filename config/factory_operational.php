<?php

return [
    'canonical_pipeline' => env('FACTORY_CANONICAL_PIPELINE', 'classic'),
    'lock_seconds' => (int) env('FACTORY_LOCK_SECONDS', 3600),
    'final_master_lock_seconds' => (int) env('FACTORY_FINAL_MASTER_LOCK_SECONDS', 7200),
    'generated_api_middleware' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('FACTORY_GENERATED_API_MIDDLEWARE', 'auth:sanctum')),
    ))),
    'pipelines' => [
        'classic',
        'enterprise',
    ],
];
