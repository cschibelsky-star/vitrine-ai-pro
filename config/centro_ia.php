<?php

return [
    'internal_token' => env('CENTRO_IA_INTERNAL_TOKEN'),

    'capabilities' => [
        'course_generation' => [
            'agent_id' => env('CENTRO_IA_COURSE_GENERATION_AGENT_ID'),
            'agent_slug' => env('CENTRO_IA_COURSE_GENERATION_AGENT_SLUG'),
        ],
    ],
];
