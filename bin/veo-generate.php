<?php

declare(strict_types=1);

use App\Shared\AI\Video\VideoEngine;
use App\Shared\AI\Video\VideoRequest;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$options = getopt('', [
    'request-id:',
    'title:',
    'prompt:',
    'aspect-ratio:',
    'duration:',
    'resolution:',
    'provider::',
]);

$required = ['request-id', 'title', 'prompt', 'aspect-ratio', 'duration', 'resolution'];
foreach ($required as $key) {
    if (! isset($options[$key]) || trim((string) $options[$key]) === '') {
        fwrite(STDERR, json_encode(['ok' => false, 'error' => 'missing_option', 'option' => $key]).PHP_EOL);
        exit(2);
    }
}

$aspectRatio = (string) $options['aspect-ratio'];
$duration = (int) $options['duration'];
$resolution = strtolower((string) $options['resolution']);
$provider = (string) ($options['provider'] ?? 'gemini_veo');

if (! in_array($aspectRatio, ['9:16', '16:9'], true)) {
    fwrite(STDERR, json_encode(['ok' => false, 'error' => 'invalid_aspect_ratio']).PHP_EOL);
    exit(2);
}
if (! in_array($duration, [4, 6, 8], true)) {
    fwrite(STDERR, json_encode(['ok' => false, 'error' => 'invalid_duration']).PHP_EOL);
    exit(2);
}
if (! in_array($resolution, ['720p', '1080p', '4k'], true)) {
    fwrite(STDERR, json_encode(['ok' => false, 'error' => 'invalid_resolution']).PHP_EOL);
    exit(2);
}
if (in_array($resolution, ['1080p', '4k'], true) && $duration !== 8) {
    fwrite(STDERR, json_encode(['ok' => false, 'error' => 'high_resolution_requires_8_seconds']).PHP_EOL);
    exit(2);
}
if ($provider !== 'gemini_veo') {
    fwrite(STDERR, json_encode(['ok' => false, 'error' => 'provider_not_allowed']).PHP_EOL);
    exit(2);
}

$request = new VideoRequest(
    requestId: (string) $options['request-id'],
    source: 'marketing_agents',
    sourceId: 'video_producer',
    title: (string) $options['title'],
    script: (string) $options['prompt'],
    aspectRatios: [$aspectRatio],
    durationSeconds: $duration,
    metadata: [
        'resolution' => $resolution,
        'auto_regenerate' => false,
        'auto_publish' => false,
    ],
);

try {
    $session = app(VideoEngine::class)->generate($request, $provider);
    echo json_encode([
        'ok' => true,
        'session' => $session->toArray(),
        'auto_regenerate' => false,
        'auto_publish' => false,
    ], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, json_encode([
        'ok' => false,
        'error' => $exception::class,
        'message' => mb_substr($exception->getMessage(), 0, 500),
    ], JSON_UNESCAPED_SLASHES).PHP_EOL);
    exit(1);
}
