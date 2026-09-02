<?php

declare(strict_types=1);

return [
    'default_provider' => env('AI_HUB_DEFAULT_PROVIDER', 'roteia'),

    'credit' => [
        'brl_per_credit' => (float) env('AI_HUB_BRL_PER_CREDIT', 0.01),
        'markup_multiplier' => (float) env('AI_HUB_MARKUP_MULTIPLIER', 1.00),
    ],

    // Arquitetura oficial: Roteia é o gateway transversal de IA do ecossistema.
    // HeyGen é uma exceção especializada e só pode atender TV Sumaré e Cursos IA.
    'provider_scopes' => [
        'roteia' => [
            'scope' => 'ecosystem',
            'allowed_projects' => ['*'],
        ],
        'heygen' => [
            'scope' => 'restricted_video',
            'allowed_projects' => ['tvsumare', 'cursos-ia-mvp'],
            'allowed_capabilities' => ['video_generation'],
        ],
    ],

    'providers' => [
        'roteia' => [
            'driver' => 'openai-compatible',
            'base_url' => env('ROTEIA_BASE_URL', 'https://api.roteia.ai/v1'),
            'api_key' => env('ROTEIA_API_KEY'),
            'timeout' => (int) env('ROTEIA_TIMEOUT', 60),
            'mode' => env('ROTEIA_MODE', 'experimental'),
        ],
    ],
];
