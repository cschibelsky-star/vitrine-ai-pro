<?php

declare(strict_types=1);

/*
 * Snapshot from the user-provided Invision HTML catalog, 2026-10-24.
 * These are display prices, NOT verified runtime billing rates.
 * The routing engine must not select a quote until separately verified,
 * validated for the active account/plan and given a short expiration.
 */
return [
    'snapshot_date' => '2026-10-24',
    'source' => 'https://invision.dreamvision.com.br/modelos',
    'unit' => 'USD per 1 million tokens',
    'quotes' => [
        ['provider' => 'invision', 'model_id' => 'openai/gpt-6.1-sol-invision', 'input_per_million' => 1.23, 'output_per_million' => 6.15, 'currency' => 'USD', 'verified' => false],
        ['provider' => 'invision', 'model_id' => 'anthropic/claude-sonnet-5.5', 'input_per_million' => 1.56, 'output_per_million' => 6.15, 'currency' => 'USD', 'verified' => false],
        ['provider' => 'invision', 'model_id' => 'anthropic/claude-haiku-5.5', 'input_per_million' => 0.078, 'output_per_million' => 0.308, 'currency' => 'USD', 'verified' => false],
        ['provider' => 'invision', 'model_id' => 'google/gemini-3.7-flash', 'input_per_million' => 0.539, 'output_per_million' => 2.69, 'currency' => 'USD', 'verified' => false],
        ['provider' => 'invision', 'model_id' => 'openai/gpt-oss-120b', 'input_per_million' => 0.191, 'output_per_million' => 0.762, 'currency' => 'USD', 'verified' => false],
    ],
];
