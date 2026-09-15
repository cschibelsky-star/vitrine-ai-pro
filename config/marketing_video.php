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

    /*
     * Final video pipeline:
     * provider -> controlled ingest -> final editor -> approval -> Drive/publisher.
     * Provider URLs are transport only and must never be treated as campaign masters.
     */
    'finalization' => [
        'working_directory' => env('MARKETING_VIDEO_MEDIA_ROOT'),
        'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
        'ffprobe_binary' => env('FFPROBE_BINARY', 'ffprobe'),
        'official_logo_path' => env('MARKETING_VIDEO_OFFICIAL_LOGO'),
        'allowed_hosts' => [
            'files2.heygen.ai',
            'resource2.heygen.ai',
            'resource.heygen.ai',
            'video.heygen.com',
        ],
    ],
];
