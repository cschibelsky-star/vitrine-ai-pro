<?php

return [
    'canonical_pipeline' => env('FACTORY_CANONICAL_PIPELINE', 'classic'),
    'lock_seconds' => (int) env('FACTORY_LOCK_SECONDS', 3600),
    'final_master_lock_seconds' => (int) env('FACTORY_FINAL_MASTER_LOCK_SECONDS', 7200),
    'pipelines' => [
        'classic',
        'enterprise',
    ],
];
