<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('AVELLO_ENABLED', false),
    'key' => env('AVELLO_API_KEY', ''),
    'base_url' => env('AVELLO_BASE_URL', 'https://avello.online/v1'),
    'models' => ['gemini-3.1-flash-lite', 'qwen3.8-max', 'devstral-medium'],
];
