<?php

declare(strict_types=1);

return [
    'enabled' => env('FACTORY_ENGINE_ENABLED', true),

    'providers' => [
        'internal' => [
            'enabled' => true,
        ],
        'openai' => [
            'enabled' => env('FACTORY_OPENAI_ENABLED', false),
        ],
        'gemini' => [
            'enabled' => env('FACTORY_GEMINI_ENABLED', false),
        ],
        'claude' => [
            'enabled' => env('FACTORY_CLAUDE_ENABLED', false),
        ],
    ],
];
