<?php

declare(strict_types=1);

return [
    'enabled' => env('VIA_SENTINEL_ENABLED', true),
    'mode' => env('VIA_SENTINEL_MODE', 'OBSERVER'),
    'snapshot_retention_days' => (int) env('VIA_SENTINEL_SNAPSHOT_RETENTION_DAYS', 14),
    'poll_seconds' => (int) env('VIA_SENTINEL_POLL_SECONDS', 60),
    'ai_budget_attention_percent' => (float) env('VIA_SENTINEL_AI_BUDGET_ATTENTION_PERCENT', 80),
    'ai_budget_alert_percent' => (float) env('VIA_SENTINEL_AI_BUDGET_ALERT_PERCENT', 95),
    'ecosystem_enabled' => env('VIA_SENTINEL_ECOSYSTEM_ENABLED', true),
    'ecosystem_url' => env('VIA_SENTINEL_ECOSYSTEM_URL', 'http://vae_core:3091/api/vae/ecosystem'),
    'ecosystem_timeout_seconds' => (int) env('VIA_SENTINEL_ECOSYSTEM_TIMEOUT_SECONDS', 6),
    'stale_after_seconds' => (int) env('VIA_SENTINEL_STALE_AFTER_SECONDS', 600),
];
