<?php

declare(strict_types=1);

return [
    'provider' => env('MARKETING_VIDEO_PROVIDER', 'gemini_veo'),

    'gemini_veo' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'model' => env('GEMINI_VEO_MODEL', 'veo-3.1-generate-preview'),
        'aspect_ratio' => env('GEMINI_VEO_ASPECT_RATIO', '9:16'),
        'resolution' => env('GEMINI_VEO_RESOLUTION', '720p'),
        'timeout_seconds' => 30,
    ],
];
