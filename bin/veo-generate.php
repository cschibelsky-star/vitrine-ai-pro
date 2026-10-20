<?php

declare(strict_types=1);

use App\Shared\AI\Video\VideoEngine;
use App\Shared\AI\Video\VideoRequest;
use Illuminate\Contracts\Console\Kernel;

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

// Optional request-scoped envelope for owner videos; plain-text callers retain their defaults.
$prompt = (string) $options['prompt'];
$personal = false;
$costUsd = null;
$envelope = json_decode($prompt, true);
if (is_array($envelope) && array_key_exists('execution_context', $envelope)) {
    $reject = static function (string $error): never {
        fwrite(STDERR, json_encode(['ok' => false, 'error' => $error, 'generation_executed' => false]).PHP_EOL);
        exit(2);
    };
    if ($envelope['execution_context'] !== 'personal-video-v1'
        || ! str_starts_with((string) $options['request-id'], 'PERSONAL-')) {
        $reject('personal_context_required');
    }
    $unknown = array_diff(array_keys($envelope), ['execution_context', 'model', 'prompt', 'max_cost_usd', 'dry_run']);
    if ($unknown !== []) {
        $reject('unknown_personal_option');
    }
    if (($envelope['model'] ?? null) !== 'veo-3.1-lite-generate-preview' || $resolution !== '720p') {
        $reject('personal_model_or_resolution_not_allowed');
    }
    if (! is_string($envelope['prompt'] ?? null) || trim($envelope['prompt']) === '') {
        $reject('personal_prompt_required');
    }
    if (isset($envelope['dry_run']) && ! is_bool($envelope['dry_run'])) {
        $reject('invalid_dry_run');
    }
    $budget = $envelope['max_cost_usd'] ?? null;
    $costUsd = round($duration * 0.05, 4);
    if ((! is_int($budget) && ! is_float($budget)) || ! is_finite((float) $budget)
        || $budget <= 0 || $budget > 0.40 || $costUsd > $budget) {
        $reject('personal_cost_limit_exceeded');
    }
    $personal = true;
    $prompt = trim($envelope['prompt']);
    if (($envelope['dry_run'] ?? false) === true) {
        echo json_encode([
            'ok' => true, 'dry_run' => true, 'generation_executed' => false,
            'model' => $envelope['model'], 'duration_seconds' => $duration,
            'resolution' => $resolution, 'estimated_cost_usd' => $costUsd,
            'source' => 'personal', 'campaign_created' => false,
            'http_attempts' => 1, 'auto_regenerate' => false, 'auto_publish' => false,
        ], JSON_UNESCAPED_SLASHES).PHP_EOL);
        exit(0);
    }
}

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if ($personal) {
    // These overrides live only in this CLI process, never in shared runtime settings.
    config([
        'marketing_video.gemini_veo.model' => 'veo-3.1-lite-generate-preview',
        'marketing_video.gemini_veo.http_retries' => 1,
    ]);
}

$request = new VideoRequest(
    requestId: (string) $options['request-id'],
    source: $personal ? 'personal' : 'marketing_agents',
    sourceId: $personal ? 'owner_video' : 'video_producer',
    title: (string) $options['title'],
    script: $prompt,
    aspectRatios: [$aspectRatio],
    durationSeconds: $duration,
    metadata: [
        'resolution' => $resolution,
        'auto_regenerate' => false,
        'auto_publish' => false,
    ],
);

try {
    $engine = $personal
        ? new VideoEngine([new \App\Shared\AI\Video\GeminiVeoVideoProvider()])
        : app(VideoEngine::class);
    $session = $engine->generate($request, $provider);
    echo json_encode([
        'ok' => true,
        'session' => $session->toArray(),
        'auto_regenerate' => false,
        'auto_publish' => false,
    ], JSON_UNESCAPED_SLASHES).PHP_EOL;
} catch (Throwable $exception) {
    if ($personal && preg_match('/HTTP ([0-9]{3}): (.*)/s', $exception->getMessage(), $matches)) {
        $providerError = json_decode($matches[2], true);
        fwrite(STDERR, json_encode([
            'ok' => false,
            'model' => 'veo-3.1-lite-generate-preview',
            'http_status' => (int) $matches[1],
            'provider_status' => $providerError['error']['status'] ?? null,
            'message' => $providerError['error']['message'] ?? 'Provider rejected request',
            'quota_details' => $providerError['error']['details'] ?? [],
            'generation_executed' => false,
            'http_attempts' => 1,
        ], JSON_UNESCAPED_SLASHES).PHP_EOL;
        exit(1);
    }
    fwrite(STDERR, json_encode([
        'ok' => false,
        'error' => $exception::class,
        'message' => mb_substr($exception->getMessage(), 0, 500),
    ], JSON_UNESCAPED_SLASHES).PHP_EOL);
    exit(1);
}
