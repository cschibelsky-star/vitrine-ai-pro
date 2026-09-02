<?php

declare(strict_types=1);

return [
    'enabled' => env('VIA_AGENT_HUB_ENABLED', true),
    'mode' => env('VIA_AGENT_HUB_MODE', 'OBSERVER'),
    'project_id' => env('VIA_AGENT_HUB_PROJECT_ID', 'via-agent-hub'),
    'default_domain' => env('VIA_AGENT_HUB_DEFAULT_DOMAIN', 'factory'),
    'allowed_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('VIA_AGENT_HUB_ALLOWED_DOMAINS', 'factory'))
    ))),
    'default_profile' => env('VIA_AGENT_HUB_DEFAULT_PROFILE', 'balanced'),
    'mission_runtime' => [
        'max_output_tokens' => (int) env('VIA_AGENT_HUB_MAX_OUTPUT_TOKENS', 6500),
        'max_context_chars' => (int) env('VIA_AGENT_HUB_MAX_CONTEXT_CHARS', 12000),
        'temperature' => (float) env('VIA_AGENT_HUB_TEMPERATURE', 0.1),
        'report_format' => 'compact_v1',
    ],
    'capabilities' => [
        'read' => true,
        'analyze' => true,
        'recommend' => true,
        'write' => false,
        'deploy' => false,
        'destructive_actions' => false,
    ],
];
