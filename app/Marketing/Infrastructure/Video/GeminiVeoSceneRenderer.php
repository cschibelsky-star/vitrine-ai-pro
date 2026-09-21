<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Video;

use App\Marketing\Application\VideoSceneRenderer;
use App\Marketing\Domain\Video\VideoProject;
use App\Marketing\Domain\Video\VideoScene;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

final class GeminiVeoSceneRenderer implements VideoSceneRenderer
{
    public function dispatch(VideoProject $project, VideoScene $scene, array $context = []): array
    {
        $prompt = trim((string) ($scene->content()['prompt'] ?? $scene->content()['script'] ?? $scene->content()['text'] ?? ''));
        if ($prompt === '') {
            throw new InvalidArgumentException('video_scene_prompt_required:'.$scene->sceneId);
        }

        $model = trim((string) config('marketing_video.gemini_veo.model', 'veo-3.1-generate-preview'));
        $aspectRatio = (string) ($context['aspect_ratio'] ?? config('marketing_video.gemini_veo.aspect_ratio', '9:16'));
        $resolution = (string) ($context['resolution'] ?? config('marketing_video.gemini_veo.resolution', '720p'));
        $duration = (int) ($context['duration_seconds'] ?? 8);

        if (! in_array($aspectRatio, ['9:16', '16:9'], true)) {
            throw new InvalidArgumentException('veo_aspect_ratio_invalid');
        }
        if (! in_array($duration, [4, 6, 8], true)) {
            throw new InvalidArgumentException('veo_duration_invalid');
        }

        try {
            $dynamic = $this->dispatchThroughCentroIa($prompt, $aspectRatio, $duration);
            if ($dynamic !== null) {
                return $dynamic;
            }
        } catch (\Throwable $routingException) {
            logger()->warning('Marketing IA: orquestrador dinâmico de vídeo indisponível; tentando Veo direto.', [
                'error' => $routingException->getMessage(),
            ]);
        }

        $payload = [
            'instances' => [[
                'prompt' => $prompt,
            ]],
            'parameters' => [
                'aspectRatio' => $aspectRatio,
                'resolution' => $resolution,
                'durationSeconds' => $duration,
                'sampleCount' => 1,
            ],
        ];

        $response = $this->client()->post(sprintf('/models/%s:predictLongRunning', $model), $payload);
        if (! $response->successful()) {
            if ($response->status() === 402 || str_contains(strtoupper((string) $response->body()), 'RESOURCE_EXHAUSTED')) {
                throw new RuntimeException('gemini_veo_quota_exhausted');
            }

            throw new RuntimeException('gemini_veo_dispatch_failed:'.$response->status());
        }

        $operation = trim((string) $response->json('name', ''));
        if ($operation === '') {
            throw new RuntimeException('gemini_veo_operation_missing');
        }

        return [
            'provider' => 'gemini_veo',
            'status' => 'processing',
            'job_ref' => $operation,
            'render_ref' => null,
        ];
    }

    public function refresh(string $jobRef): array
    {
        $jobRef = trim($jobRef);

        if (str_starts_with($jobRef, 'centroia:')) {
            return $this->refreshThroughCentroIa($jobRef);
        }

        if ($jobRef === '' || ! str_starts_with($jobRef, 'operations/')) {
            throw new InvalidArgumentException('gemini_veo_job_ref_invalid');
        }

        $response = $this->client()->get('/'.$jobRef);
        if (! $response->successful()) {
            throw new RuntimeException('gemini_veo_refresh_failed:'.$response->status());
        }

        if (! (bool) $response->json('done', false)) {
            return [
                'provider' => 'gemini_veo',
                'status' => 'processing',
                'job_ref' => $jobRef,
                'render_ref' => null,
            ];
        }

        if ($response->json('error') !== null) {
            return [
                'provider' => 'gemini_veo',
                'status' => 'failed',
                'job_ref' => $jobRef,
                'render_ref' => null,
            ];
        }

        $renderRef = $response->json('response.generateVideoResponse.generatedSamples.0.video.uri')
            ?? $response->json('response.generatedVideos.0.video.uri');

        if (! is_string($renderRef) || trim($renderRef) === '') {
            throw new RuntimeException('gemini_veo_render_ref_missing');
        }

        return [
            'provider' => 'gemini_veo',
            'status' => 'completed',
            'job_ref' => $jobRef,
            'render_ref' => $renderRef,
        ];
    }

    private function dispatchThroughCentroIa(string $prompt, string $aspectRatio, int $duration): ?array
    {
        $hub = (array) config('marketing_agents.hub', []);
        $url = trim((string) ($hub['url'] ?? ''));
        $token = trim((string) ($hub['token'] ?? ''));
        $projectId = trim((string) ($hub['project_id'] ?? 'vitrine-marketing-agents-core'));

        if ($url === '' || $token === '') {
            return null;
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->withHeaders(['X-Vitrine-Project' => $projectId])
            ->timeout(180)
            ->retry(1, 300, throw: false)
            ->post($url, [
                'project_id' => $projectId,
                'capability' => 'video_generation',
                'input' => [
                    'user' => $prompt,
                    'material_type' => 'social_video',
                    'quality_profile' => 'balanced',
                    'duration_seconds' => $duration,
                    'aspect_ratio' => $aspectRatio,
                ],
            ]);

        if (! $response->successful() || ! $response->json('ok')) {
            throw new RuntimeException('dynamic_video_router:'.(string) ($response->json('error') ?? ('http_'.$response->status())));
        }

        $status = strtolower(trim((string) $response->json('media_status', 'processing')));
        $assetUrl = trim((string) $response->json('asset_url', ''));
        $providerRef = trim((string) $response->json('job_ref', ''));
        $model = trim((string) $response->json('model', ''));

        if ($status === 'completed' && $assetUrl !== '') {
            return [
                'provider' => 'centro_ia',
                'model' => $model,
                'status' => 'completed',
                'job_ref' => '',
                'render_ref' => $assetUrl,
            ];
        }

        if ($providerRef === '') {
            throw new RuntimeException('dynamic_video_router_job_ref_missing');
        }

        $encoded = base64_encode(json_encode([
            'job_ref' => $providerRef,
            'model' => $model,
        ], JSON_UNESCAPED_SLASHES) ?: '{}');

        return [
            'provider' => 'centro_ia',
            'model' => $model,
            'status' => 'processing',
            'job_ref' => 'centroia:'.$encoded,
            'render_ref' => null,
        ];
    }

    private function refreshThroughCentroIa(string $jobRef): array
    {
        $encoded = substr($jobRef, strlen('centroia:'));
        $decoded = json_decode((string) base64_decode($encoded, true), true);
        $providerRef = trim((string) ($decoded['job_ref'] ?? ''));

        if ($providerRef === '') {
            throw new InvalidArgumentException('centro_ia_video_job_ref_invalid');
        }

        $hub = (array) config('marketing_agents.hub', []);
        $url = trim((string) ($hub['url'] ?? ''));
        $token = trim((string) ($hub['token'] ?? ''));
        $projectId = trim((string) ($hub['project_id'] ?? 'vitrine-marketing-agents-core'));

        if ($url === '' || $token === '') {
            throw new RuntimeException('centro_ia_video_refresh_unavailable');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($token)
            ->withHeaders(['X-Vitrine-Project' => $projectId])
            ->timeout(45)
            ->retry(1, 300, throw: false)
            ->post($url, [
                'project_id' => $projectId,
                'capability' => 'video_generation',
                'input' => [
                    'user' => 'Atualize o estado desta geração de vídeo.',
                    'operation' => 'refresh',
                    'job_ref' => $providerRef,
                ],
            ]);

        if (! $response->successful() || ! $response->json('ok')) {
            throw new RuntimeException('centro_ia_video_refresh_failed:'.(string) ($response->json('error') ?? $response->status()));
        }

        $status = strtolower(trim((string) $response->json('media_status', 'processing')));
        $assetUrl = trim((string) $response->json('asset_url', ''));

        return [
            'provider' => 'centro_ia',
            'status' => $status === 'completed' ? 'completed' : ($status === 'failed' ? 'failed' : 'processing'),
            'job_ref' => $jobRef,
            'render_ref' => $assetUrl !== '' ? $assetUrl : null,
        ];
    }

    private function client(): PendingRequest
    {
        $apiKey = trim((string) config('marketing_video.gemini_veo.api_key'));
        if ($apiKey === '') {
            throw new RuntimeException('gemini_api_key_missing');
        }

        return Http::baseUrl(rtrim((string) config('marketing_video.gemini_veo.base_url'), '/'))
            ->acceptJson()
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->timeout((int) config('marketing_video.gemini_veo.timeout_seconds', 30));
    }
}
