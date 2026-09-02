<?php

declare(strict_types=1);

$projectLimits = json_decode((string) env('AI_DEV_HUB_PROJECT_LIMITS_BRL', '{}'), true);

return [
    'enabled' => env('AI_DEV_HUB_ENABLED', true),
    'internal_token' => env('AI_DEV_HUB_INTERNAL_TOKEN', env('CENTRO_IA_INTERNAL_TOKEN')),
    'default_provider' => env('AI_DEV_HUB_DEFAULT_PROVIDER', 'roteia'),
    'max_compare_models' => (int) env('AI_DEV_HUB_MAX_COMPARE_MODELS', 3),
    'monthly_limit_brl' => (float) env('AI_DEV_HUB_MONTHLY_LIMIT_BRL', 50),
    'project_monthly_limits_brl' => is_array($projectLimits) ? $projectLimits : [],
    'allowed_projects' => array_values(array_filter(array_map('trim', explode(',', (string) env('AI_DEV_HUB_ALLOWED_PROJECTS', 'vitrine-ia-pro-core,via-agent-hub,agente-compras-ia,tvsumare,cursos-ia-mvp'))))),
    'profiles' => [
        'economy' => [
            'tier' => 'economy',
            'tiers' => ['economy'],
        ],
        'balanced' => [
            'tier' => 'balanced',
            'tiers' => ['balanced', 'economy'],
        ],
        'premium' => [
            'tier' => 'premium',
            'tiers' => ['premium', 'balanced', 'economy'],
        ],
    ],
];
