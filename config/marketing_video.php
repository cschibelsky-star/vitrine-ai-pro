<?php

declare(strict_types=1);

return [
    'provider' => env('MARKETING_VIDEO_PROVIDER', 'gemini_veo'),

    'gemini_veo' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_VEO_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_VEO_MODEL', 'veo-3.1-generate-preview'),
        'aspect_ratio' => env('GEMINI_VEO_ASPECT_RATIO', '9:16'),
        'resolution' => env('GEMINI_VEO_RESOLUTION', '720p'),
        'duration_seconds' => (int) env('GEMINI_VEO_DURATION_SECONDS', 8),
        'connect_timeout_seconds' => (int) env('GEMINI_VEO_CONNECT_TIMEOUT', 10),
        'timeout_seconds' => (int) env('GEMINI_VEO_TIMEOUT', 30),
        'poll_interval_seconds' => (int) env('GEMINI_VEO_POLL_INTERVAL', 10),
        'max_wait_seconds' => (int) env('GEMINI_VEO_MAX_WAIT', 360),
        'http_retries' => (int) env('GEMINI_VEO_HTTP_RETRIES', 2),
        'http_retry_delay_ms' => (int) env('GEMINI_VEO_HTTP_RETRY_DELAY_MS', 500),
    ],

    'finalization' => [
        'working_directory' => env('MARKETING_VIDEO_MEDIA_ROOT'),
        'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
        'ffprobe_binary' => env('FFPROBE_BINARY', 'ffprobe'),
        'official_logo_path' => env('MARKETING_VIDEO_OFFICIAL_LOGO', base_path('assets/img/logo-vitrine-ai-pro.png')),
        'allowed_hosts' => [
            'files2.heygen.ai',
            'resource2.heygen.ai',
            'resource.heygen.ai',
            'video.heygen.com',
            'generativelanguage.googleapis.com',
        ],
    ],
];
