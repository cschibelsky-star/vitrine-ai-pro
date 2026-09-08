<?php

return [
    'internal_token' => env('CENTRO_IA_INTERNAL_TOKEN'),

    'capabilities' => [
        'course_generation' => [
            'agent_id' => env('CENTRO_IA_COURSE_GENERATION_AGENT_ID'),
            'agent_slug' => env('CENTRO_IA_COURSE_GENERATION_AGENT_SLUG'),
        ],
        'social_content_generation' => [
            'agent_slug' => env('CENTRO_IA_SOCIAL_CONTENT_AGENT_SLUG', 'marketing-ia'),
        ],
        'marketing_generation' => [
            'agent_slug' => env('CENTRO_IA_MARKETING_AGENT_SLUG', 'marketing-ia'),
        ],
        'editorial_generation' => [
            'agent_slug' => env('CENTRO_IA_EDITORIAL_AGENT_SLUG', 'editorial-ia'),
        ],
    ],
];
