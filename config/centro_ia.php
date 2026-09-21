<?php

return [
    'internal_token' => env('CENTRO_IA_INTERNAL_TOKEN'),
    'heygen_webhook_secret' => env('HEYGEN_WEBHOOK_SECRET'),

    'cost_center' => [
        // Fixed operational FX reference used only when a gateway returns USD cost.
        // Keep configurable so finance can update it without code changes.
        'usd_brl_rate' => (float) env('AI_COST_USD_BRL_RATE', 5.50),
    ],

    'service_identities' => [
        'vitrine-ai-social-enterprise' => [
            'public_key_url' => env(
                'CENTRO_IA_SOCIAL_PUBLIC_KEY_URL',
                'https://social.hml.vitrineiapro.com.br/service-identity/public-key'
            ),
            'max_clock_skew' => (int) env('CENTRO_IA_SERVICE_MAX_CLOCK_SKEW', 90),
        ],
    ],

    'capabilities' => [
        'course_generation' => [
            'agent_id' => env('CENTRO_IA_COURSE_GENERATION_AGENT_ID'),
            'agent_slug' => env('CENTRO_IA_COURSE_GENERATION_AGENT_SLUG'),
        ],
        'social_content_generation' => [
            'agent_slug' => env('CENTRO_IA_SOCIAL_CONTENT_AGENT_SLUG', 'marketing-ia'),
            'routing_capability' => 'copy',
        ],
        'marketing_generation' => [
            'agent_slug' => env('CENTRO_IA_MARKETING_AGENT_SLUG', 'marketing-ia'),
            'routing_capability' => 'marketing_strategy',
        ],
        'editorial_generation' => [
            'agent_slug' => env('CENTRO_IA_EDITORIAL_AGENT_SLUG', 'editorial-ia'),
            'routing_capability' => 'copy',
        ],
        'image_generation' => [
            'agent_slug' => env('CENTRO_IA_IMAGE_AGENT_SLUG', 'marketing-ia'),
            'routing_capability' => 'image_generation',
        ],
        'video_generation' => [
            'agent_slug' => env('CENTRO_IA_VIDEO_AGENT_SLUG', 'marketing-ia'),
            'routing_capability' => 'video_generation',
        ],
        'avatar_video' => [
            'agent_slug' => env('CENTRO_IA_AVATAR_VIDEO_AGENT_SLUG', 'marketing-ia'),
            'routing_capability' => 'avatar_video',
        ],
    ],

    'media_orchestrator' => [
        'profiles' => [
            'balanced' => ['quality' => 0.40, 'suitability' => 0.25, 'cost' => 0.20, 'speed' => 0.10, 'reliability' => 0.05],
            'quality' => ['quality' => 0.50, 'suitability' => 0.30, 'cost' => 0.08, 'speed' => 0.07, 'reliability' => 0.05],
            'economy' => ['quality' => 0.25, 'suitability' => 0.20, 'cost' => 0.35, 'speed' => 0.15, 'reliability' => 0.05],
            'fast' => ['quality' => 0.25, 'suitability' => 0.20, 'cost' => 0.15, 'speed' => 0.35, 'reliability' => 0.05],
        ],
        'capabilities' => [
            'image' => [
                'preferred_patterns' => [
                    'x-ai/grok-imagine-image' => ['bonus' => 18, 'use' => 'premium_visual'],
                    'bytedance-seed/seedream' => ['bonus' => 16, 'use' => 'premium_visual'],
                    'google/gemini-3.1-flash-image' => ['bonus' => 14, 'use' => 'balanced_visual'],
                    'flux' => ['bonus' => 10, 'use' => 'creative_visual'],
                ],
            ],
            'video' => [
                'preferred_patterns' => [
                    'google/veo' => ['bonus' => 20, 'use' => 'premium_institutional'],
                    'x-ai/grok-imagine-video' => ['bonus' => 18, 'use' => 'dynamic_social'],
                    'bytedance/seedance' => ['bonus' => 18, 'use' => 'social_motion'],
                    'runway/' => ['bonus' => 17, 'use' => 'premium_motion'],
                    'minimax/hailuo' => ['bonus' => 15, 'use' => 'dynamic_motion'],
                ],
            ],
            'avatar' => [
                'preferred_patterns' => [
                    'heygen/' => ['bonus' => 30, 'use' => 'avatar_presenter'],
                ],
            ],
        ],
        'attempts' => [
            'max_candidates' => 6,
            'max_per_provider' => 2,
        ],
    ],
];
