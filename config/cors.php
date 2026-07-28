<?php

declare(strict_types=1);

$normalizeOrigin = static function (string $origin): ?string {
    $origin = rtrim(trim($origin), '/');

    if ($origin === '' || str_contains($origin, '*') || filter_var($origin, FILTER_VALIDATE_URL) === false) {
        return null;
    }

    $parts = parse_url($origin);

    if (
        ! is_array($parts)
        || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
        || empty($parts['host'])
        || isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['path'])
        || isset($parts['query'])
        || isset($parts['fragment'])
    ) {
        return null;
    }

    return $origin;
};

$allowedOrigins = array_values(array_filter(array_map(
    $normalizeOrigin,
    explode(',', (string) env('VENDORIA_PRO_NEWS_ALLOWED_ORIGINS', ''))
)));

if ($allowedOrigins === []) {
    $fallbackOrigin = $normalizeOrigin((string) config('app.url'));
    $allowedOrigins = $fallbackOrigin === null ? [] : [$fallbackOrigin];
}

return [
    'paths' => ['api/vendedoria-pro-news/leads'],
    'allowed_methods' => ['POST', 'OPTIONS'],
    'allowed_origins' => $allowedOrigins,
    'allowed_origins_patterns' => array_map(
        static fn (string $origin): string => '#^'.preg_quote($origin, '#').'\z#u',
        $allowedOrigins
    ),
    'allowed_headers' => ['Content-Type', 'Accept', 'Origin'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
