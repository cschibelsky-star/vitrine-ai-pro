<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Models\Subscription;
use App\Services\Ai\AiRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class CentroIaBrokerController extends Controller
{
    public function execute(Request $request, AiRoutingService $service, AiUsageTelemetry $usageTelemetry): JsonResponse
    {
        if (! $this->isAuthorized($request)) {
            return response()->json([
                'ok' => false,
                'error' => 'unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'capability' => ['required', 'string', 'max:120'],
            'input' => ['required', 'array'],
            'input.system' => ['nullable', 'string', 'max:20000'],
            'input.user' => ['required', 'string', 'min:5', 'max:120000'],
            'input.response_format' => ['nullable', 'string', 'max:30'],
            'input.temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'input.material_type' => ['nullable', 'string', 'max:80'],
            'input.quality_profile' => ['nullable', 'string', 'max:40'],
            'input.duration_seconds' => ['nullable', 'integer', 'min:1', 'max:60'],
            'input.aspect_ratio' => ['nullable', 'string', 'max:12'],
            'input.operation' => ['nullable', 'string', 'max:30'],
            'input.job_ref' => ['nullable', 'string', 'max:500'],
        ]);

        $projectHeader = trim((string) $request->header('X-Vitrine-Project', ''));
        if ($projectHeader === '' || ! hash_equals((string) $data['project_id'], $projectHeader)) {
            return response()->json([
                'ok' => false,
                'error' => 'project_identity_mismatch',
            ], 422);
        }

        $capability = (string) $data['capability'];
        $capabilityConfig = config('centro_ia.capabilities.' . $capability);

        if (! is_array($capabilityConfig)) {
            return response()->json([
                'ok' => false,
                'error' => 'capability_not_supported',
                'capability' => $capability,
            ], 422);
        }

        $agent = $this->resolveAgent($capabilityConfig);

        if (! $agent) {
            return response()->json([
                'ok' => false,
                'error' => 'capability_agent_not_configured',
                'capability' => $capability,
            ], 503);
        }

        $system = trim((string) ($data['input']['system'] ?? ''));
        $user = trim((string) $data['input']['user']);
        $prompt = $system !== ''
            ? "INSTRUCOES DO SISTEMA:\n{$system}\n\nSOLICITACAO:\n{$user}"
            : $user;

        $routingCapability = trim((string) ($capabilityConfig['routing_capability'] ?? ''));

        if (in_array($routingCapability, ['image_generation', 'video_generation'], true)) {
            return $this->executeDynamicMedia(
                (string) $data['project_id'],
                $capability,
                $routingCapability,
                $prompt,
                (array) ($data['input'] ?? [])
            );
        }

        $execution = $service->execute($agent, $prompt, $routingCapability !== '' ? $routingCapability : null);
        $status = (string) ($execution->status ?? '');
        $output = (string) ($execution->output ?? '');

        if ($status !== 'Concluído') {
            return response()->json([
                'ok' => false,
                'error' => 'ai_execution_failed',
                'execution_id' => $execution->id,
                'status' => $status,
                'message' => $output,
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'project_id' => $data['project_id'],
            'capability' => $capability,
            'execution_id' => $execution->id,
            'agent_id' => $agent->id,
            'model' => $execution->model_name ?? null,
            'output_text' => $output,
        ]);
    }

    private function executeDynamicMedia(
        string $projectId,
        string $capability,
        string $routingCapability,
        string $prompt,
        array $input
    ): JsonResponse {
        $apiKey = trim((string) env('ROTEIA_API_KEY', ''));
        $baseUrl = rtrim(trim((string) env('ROTEIA_BASE_URL', '')), '/');

        if ($apiKey === '' || $baseUrl === '') {
            return response()->json([
                'ok' => false,
                'error' => 'roteia_runtime_not_configured',
            ], 503);
        }

        $apiBaseUrl = str_ends_with($baseUrl, '/v1') ? $baseUrl : $baseUrl.'/v1';

        if ($routingCapability === 'video_generation'
            && strtolower(trim((string) ($input['operation'] ?? ''))) === 'refresh') {
            return $this->refreshDynamicVideo($projectId, $capability, $apiBaseUrl, $apiKey, (string) ($input['job_ref'] ?? ''));
        }

        $catalog = $this->roteiaCatalog();
        $candidates = $this->rankMediaCandidates($catalog, $routingCapability, $prompt, $input);

        if ($candidates === []) {
            return response()->json([
                'ok' => false,
                'error' => 'no_media_candidate_available',
                'routing_capability' => $routingCapability,
            ], 503);
        }

        $capabilities = $this->roteiaCapabilities($apiBaseUrl, $apiKey);
        $endpointKey = $routingCapability === 'image_generation' ? 'images' : 'videos';
        $endpoint = trim((string) data_get($capabilities, 'endpoints.'.$endpointKey, ''));

        if ($endpoint === '') {
            return response()->json([
                'ok' => false,
                'error' => 'media_endpoint_unavailable',
                'routing_capability' => $routingCapability,
                'ranked_models' => array_map(
                    fn (array $candidate): array => [
                        'model' => $candidate['model'],
                        'score' => $candidate['score'],
                        'reason' => $candidate['reason'],
                    ],
                    array_slice($candidates, 0, 4)
                ),
            ], 503);
        }

        $attempts = [];
        $maxCandidates = (int) config('centro_ia.media_orchestrator.attempts.max_candidates', 6);
        $maxPerProvider = (int) config('centro_ia.media_orchestrator.attempts.max_per_provider', 2);
        $executionCandidates = $this->diversifyMediaCandidates($candidates, $maxCandidates, $maxPerProvider);

        foreach ($executionCandidates as $candidate) {
            $model = (string) $candidate['model'];
            $payload = [
                'model' => $model,
                'prompt' => $prompt,
            ];

            if ($routingCapability === 'video_generation') {
                if (! empty($input['duration_seconds'])) {
                    $payload['duration_seconds'] = (int) $input['duration_seconds'];
                }
                if (! empty($input['aspect_ratio'])) {
                    $payload['aspect_ratio'] = (string) $input['aspect_ratio'];
                }
            }

            $requestUrl = str_starts_with($endpoint, '/v1/')
                ? preg_replace('#/v1$#', '', $apiBaseUrl).$endpoint
                : $apiBaseUrl.'/'.ltrim($endpoint, '/');

            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->timeout($routingCapability === 'video_generation' ? 180 : 120)
                ->retry(1, 300, throw: false)
                ->post($requestUrl, $payload);

            $attempts[] = [
                'model' => $model,
                'status' => $response->status(),
            ];

            if (! $response->successful()) {
                continue;
            }

            $providerPayload = (array) $response->json();

            if ($routingCapability === 'image_generation') {
                $assetUrl = data_get($providerPayload, 'data.0.url')
                    ?? data_get($providerPayload, 'asset_url')
                    ?? data_get($providerPayload, 'url');
                $assetBase64 = data_get($providerPayload, 'data.0.b64_json')
                    ?? data_get($providerPayload, 'output_image.data')
                    ?? data_get($providerPayload, 'image_base64');

                if ((! is_string($assetUrl) || trim($assetUrl) === '')
                    && (! is_string($assetBase64) || trim($assetBase64) === '')) {
                    continue;
                }

                $usageTelemetry->recordMedia(
                    gateway: 'roteia',
                    providerId: null,
                    agentId: null,
                    consumerKey: $projectId,
                    model: $model,
                    capability: $routingCapability,
                    payload: $providerPayload,
                    latencyMs: 0,
                    status: 'completed',
                    context: ['consumer_name' => $projectId],
                );

                return response()->json([
                    'ok' => true,
                    'project_id' => $projectId,
                    'capability' => $capability,
                    'model' => $model,
                    'provider' => 'roteia',
                    'media_status' => 'completed',
                    'asset_url' => is_string($assetUrl) ? $assetUrl : null,
                    'asset_base64' => is_string($assetBase64) ? $assetBase64 : null,
                    'routing' => [
                        'score' => $candidate['score'],
                        'reason' => $candidate['reason'],
                        'attempts' => $attempts,
                    ],
                ]);
            }

            $jobRef = trim((string) (
                data_get($providerPayload, 'id')
                ?? data_get($providerPayload, 'request_id')
                ?? data_get($providerPayload, 'job_id')
                ?? data_get($providerPayload, 'operation_id')
                ?? ''
            ));
            $assetUrl = data_get($providerPayload, 'data.0.url')
                ?? data_get($providerPayload, 'asset_url')
                ?? data_get($providerPayload, 'url');

            if ($jobRef === '' && (! is_string($assetUrl) || trim($assetUrl) === '')) {
                continue;
            }

            $usageTelemetry->recordMedia(
                gateway: 'roteia',
                providerId: null,
                agentId: null,
                consumerKey: $projectId,
                model: $model,
                capability: $routingCapability,
                payload: $providerPayload,
                latencyMs: 0,
                status: is_string($assetUrl) && trim($assetUrl) !== '' ? 'completed' : 'processing',
                context: [
                    'consumer_name' => $projectId,
                    'duration_seconds' => $input['duration_seconds'] ?? null,
                    'request_id' => $jobRef !== '' ? $jobRef : null,
                ],
            );

            return response()->json([
                'ok' => true,
                'project_id' => $projectId,
                'capability' => $capability,
                'model' => $model,
                'provider' => 'roteia',
                'media_status' => is_string($assetUrl) && trim($assetUrl) !== '' ? 'completed' : 'processing',
                'job_ref' => $jobRef !== '' ? $jobRef : null,
                'asset_url' => is_string($assetUrl) ? $assetUrl : null,
                'routing' => [
                    'score' => $candidate['score'],
                    'reason' => $candidate['reason'],
                    'attempts' => $attempts,
                ],
            ]);
        }

        return response()->json([
            'ok' => false,
            'error' => 'all_media_candidates_failed',
            'routing_capability' => $routingCapability,
            'attempts' => $attempts,
        ], 502);
    }

    private function refreshDynamicVideo(
        string $projectId,
        string $capability,
        string $apiBaseUrl,
        string $apiKey,
        string $jobRef
    ): JsonResponse {
        $jobRef = trim($jobRef);
        if ($jobRef === '') {
            return response()->json([
                'ok' => false,
                'error' => 'media_job_ref_required',
            ], 422);
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->get($apiBaseUrl.'/generations/'.rawurlencode($jobRef));

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'error' => 'media_generation_refresh_failed',
                'provider_status' => $response->status(),
            ], 502);
        }

        $payload = (array) $response->json();
        $status = strtolower(trim((string) (
            $payload['status']
            ?? data_get($payload, 'data.status')
            ?? 'processing'
        )));
        $assetUrl = data_get($payload, 'data.0.url')
            ?? data_get($payload, 'data.url')
            ?? data_get($payload, 'asset_url')
            ?? data_get($payload, 'url');

        $failed = in_array($status, ['failed', 'error', 'erro', 'cancelled', 'canceled'], true);
        $completed = in_array($status, ['completed', 'complete', 'done', 'success', 'succeeded', 'concluido', 'concluído'], true)
            || (is_string($assetUrl) && trim($assetUrl) !== '');

        return response()->json([
            'ok' => true,
            'project_id' => $projectId,
            'capability' => $capability,
            'provider' => 'roteia',
            'media_status' => $failed ? 'failed' : ($completed ? 'completed' : 'processing'),
            'job_ref' => $jobRef,
            'asset_url' => is_string($assetUrl) ? $assetUrl : null,
        ]);
    }

    private function roteiaCatalog(): array
    {
        return Cache::remember('centro-ia:roteia:catalog', 60, function (): array {
            try {
                $response = Http::acceptJson()
                    ->timeout(15)
                    ->get('https://api.roteia.ai/catalog/models');

                return $response->successful() ? (array) $response->json() : [];
            } catch (Throwable) {
                return [];
            }
        });
    }

    private function roteiaCapabilities(string $apiBaseUrl, string $apiKey): array
    {
        return Cache::remember('centro-ia:roteia:capabilities', 60, function () use ($apiBaseUrl, $apiKey): array {
            try {
                $response = Http::withToken($apiKey)
                    ->acceptJson()
                    ->timeout(15)
                    ->get($apiBaseUrl.'/capabilities');

                return $response->successful() ? (array) $response->json() : [];
            } catch (Throwable) {
                return [];
            }
        });
    }

    private function diversifyMediaCandidates(array $candidates, int $limit = 6, int $perProvider = 2): array
    {
        $selected = [];
        $providerCounts = [];

        foreach ($candidates as $candidate) {
            $model = (string) ($candidate['model'] ?? '');
            $provider = str_contains($model, '/') ? explode('/', $model, 2)[0] : $model;
            $count = (int) ($providerCounts[$provider] ?? 0);

            if ($count >= $perProvider) {
                continue;
            }

            $selected[] = $candidate;
            $providerCounts[$provider] = $count + 1;

            if (count($selected) >= $limit) {
                break;
            }
        }

        return $selected;
    }

    private function rankMediaCandidates(array $catalog, string $routingCapability, string $prompt, array $input): array
    {
        $target = $routingCapability === 'video_generation' ? 'video' : 'image';
        $material = strtolower(trim((string) ($input['material_type'] ?? '')));
        $qualityProfile = strtolower(trim((string) ($input['quality_profile'] ?? 'balanced')));
        $text = strtolower($prompt.' '.$material);
        $weights = (array) config('centro_ia.media_orchestrator.profiles.'.$qualityProfile, []);
        if ($weights === []) {
            $weights = (array) config('centro_ia.media_orchestrator.profiles.balanced', [
                'quality' => 0.40,
                'suitability' => 0.25,
                'cost' => 0.20,
                'speed' => 0.10,
                'reliability' => 0.05,
            ]);
        }
        $preferredPatterns = (array) config('centro_ia.media_orchestrator.capabilities.'.$target.'.preferred_patterns', []);
        $items = (array) ($catalog['data'] ?? $catalog['models'] ?? $catalog);
        $candidates = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $model = trim((string) ($item['id'] ?? $item['model'] ?? ''));
            $status = strtolower(trim((string) ($item['status'] ?? '')));
            $endpoint = strtolower(trim((string) ($item['endpoint'] ?? '')));
            $outputs = (array) data_get($item, 'modalities.output', []);

            if ($model === '' || $status !== 'available' || ! in_array($target, $outputs, true)) {
                continue;
            }

            if ($target === 'image' && $endpoint !== 'images') {
                continue;
            }
            if ($target === 'video' && $endpoint !== 'videos') {
                continue;
            }

            if ($target === 'video' && str_contains($model, 'heygen/')
                && ! str_contains($text, 'avatar')
                && ! str_contains($text, 'apresentador')
                && ! str_contains($text, 'porta-voz')) {
                continue;
            }

            $quality = 50.0;
            $marketRank = (int) ($item['marketRank'] ?? 0);
            if ($marketRank > 0) {
                $quality += max(0, 25 - min(25, $marketRank / 20));
            }

            $suitability = 20.0;
            $speed = 10.0;
            $cost = 10.0;
            $reliability = 10.0;
            $reason = [];

            foreach ($preferredPatterns as $pattern => $policy) {
                if ($pattern !== '' && str_contains($model, strtolower((string) $pattern))) {
                    $suitability += (float) ($policy['bonus'] ?? 0);
                    $use = trim((string) ($policy['use'] ?? ''));
                    if ($use !== '') {
                        $reason[] = 'matriz: '.$use;
                    }
                }
            }

            if ($target === 'video') {
                if ((str_contains($text, 'institucional') || str_contains($text, 'cinematic') || str_contains($text, 'premium'))
                    && (str_contains($model, 'veo') || str_contains($model, 'runway') || str_contains($model, 'sora'))) {
                    $suitability += 18;
                    $reason[] = 'forte para vídeo premium/institucional';
                }

                if ((str_contains($text, 'reel') || str_contains($text, 'social') || str_contains($text, 'instagram'))
                    && str_contains($model, 'seedance')) {
                    $suitability += 18;
                    $reason[] = 'adequado para vídeo social';
                }

                if ((str_contains($text, 'movimento') || str_contains($text, 'dinâmico') || str_contains($text, 'dinamico'))
                    && (str_contains($model, 'seedance') || str_contains($model, 'hailuo') || str_contains($model, 'grok'))) {
                    $suitability += 12;
                    $reason[] = 'bom ajuste para movimento';
                }

                if (str_contains($model, 'fast') || str_contains($model, 'mini') || str_contains($model, 'lite')) {
                    $speed += 12;
                    $reason[] = 'variante rápida/econômica';
                }

                if (str_contains($model, 'heygen/')) {
                    $suitability += 25;
                    $reason[] = 'especializado em avatar/apresentador';
                }
            } else {
                if (str_contains($model, 'gemini') || str_contains($model, 'flux')) {
                    $suitability += 12;
                    $reason[] = 'forte para criativo visual';
                }
                if (str_contains($model, 'flash') || str_contains($model, 'fast')) {
                    $speed += 8;
                    $reason[] = 'boa velocidade';
                }
            }

            $price = data_get($item, 'imagePriceEstimate.priceBrl')
                ?? ($item['pricePerUnitBrl'] ?? null);
            if (is_numeric($price)) {
                $price = (float) $price;
                $cost += max(0, 20 - min(20, $price * 5));
                $reason[] = 'custo conhecido no catálogo';
            }

            $score = ($quality * (float) ($weights['quality'] ?? 0.40))
                + ($suitability * (float) ($weights['suitability'] ?? 0.25))
                + ($cost * (float) ($weights['cost'] ?? 0.20))
                + ($speed * (float) ($weights['speed'] ?? 0.10))
                + ($reliability * (float) ($weights['reliability'] ?? 0.05));

            $candidates[] = [
                'model' => $model,
                'score' => round($score, 3),
                'reason' => $reason !== [] ? implode('; ', $reason) : 'compatível e disponível',
            ];
        }

        usort($candidates, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return $candidates;
    }

    public function orchestratorStatus(Request $request): JsonResponse
    {
        if (! $this->isAuthorized($request)) {
            return response()->json([
                'ok' => false,
                'error' => 'unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'quality_profile' => ['nullable', 'string', 'max:40'],
        ]);

        $projectHeader = trim((string) $request->header('X-Vitrine-Project', ''));
        if ($projectHeader === '' || ! hash_equals((string) $data['project_id'], $projectHeader)) {
            return response()->json([
                'ok' => false,
                'error' => 'project_identity_mismatch',
            ], 422);
        }

        $profile = strtolower(trim((string) ($data['quality_profile'] ?? 'balanced')));
        $catalog = $this->roteiaCatalog();
        $items = (array) ($catalog['data'] ?? $catalog['models'] ?? $catalog);

        $availability = [
            'image' => ['available' => 0, 'unavailable' => 0],
            'video' => ['available' => 0, 'unavailable' => 0],
        ];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $status = strtolower(trim((string) ($item['status'] ?? 'unavailable')));
            $outputs = (array) data_get($item, 'modalities.output', []);
            $modelType = strtolower(trim((string) ($item['modelType'] ?? '')));
            $endpoint = strtolower(trim((string) ($item['endpoint'] ?? '')));

            foreach (['image', 'video'] as $target) {
                $matches = in_array($target, $outputs, true)
                    || $modelType === $target
                    || ($target === 'image' && $endpoint === 'images')
                    || ($target === 'video' && $endpoint === 'videos');

                if (! $matches) {
                    continue;
                }

                $bucket = $status === 'available' ? 'available' : 'unavailable';
                $availability[$target][$bucket]++;
            }
        }

        $baseUrl = rtrim(trim((string) env('ROTEIA_BASE_URL', '')), '/');
        $apiKey = trim((string) env('ROTEIA_API_KEY', ''));
        $capabilities = [];
        if ($baseUrl !== '' && $apiKey !== '') {
            $apiBaseUrl = str_ends_with($baseUrl, '/v1') ? $baseUrl : $baseUrl.'/v1';
            $capabilities = $this->roteiaCapabilities($apiBaseUrl, $apiKey);
        }

        $image = $this->rankMediaCandidates(
            $catalog,
            'image_generation',
            'criativo visual social premium para campanha',
            ['material_type' => 'social_creative', 'quality_profile' => $profile]
        );

        $video = $this->rankMediaCandidates(
            $catalog,
            'video_generation',
            'video social dinamico institucional para campanha',
            ['material_type' => 'social_video', 'quality_profile' => $profile]
        );

        $summarize = static fn (array $candidate): array => [
            'model' => (string) ($candidate['model'] ?? ''),
            'score' => (float) ($candidate['score'] ?? 0),
            'reason' => (string) ($candidate['reason'] ?? ''),
        ];

        return response()->json([
            'ok' => true,
            'project_id' => (string) $data['project_id'],
            'profile' => $profile,
            'profiles' => (array) config('centro_ia.media_orchestrator.profiles', []),
            'availability' => $availability,
            'endpoints' => [
                'images' => filled(data_get($capabilities, 'endpoints.images')),
                'videos' => filled(data_get($capabilities, 'endpoints.videos')),
            ],
            'image_candidates' => array_map($summarize, array_slice($image, 0, 12)),
            'video_candidates' => array_map($summarize, array_slice($video, 0, 12)),
            'refreshed_at' => now()->toIso8601String(),
        ]);
    }

    public function entitlements(Request $request): JsonResponse
    {
        if (! $this->isAuthorized($request)) {
            return response()->json([
                'ok' => false,
                'error' => 'unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'core_subscription_id' => ['required', 'integer', 'min:1'],
        ]);

        $projectHeader = trim((string) $request->header('X-Vitrine-Project', ''));
        if ($projectHeader === '' || ! hash_equals((string) $data['project_id'], $projectHeader)) {
            return response()->json([
                'ok' => false,
                'error' => 'project_identity_mismatch',
            ], 422);
        }

        $subscription = Subscription::query()
            ->with(['plan.product', 'license', 'company'])
            ->find((int) $data['core_subscription_id']);

        if (! $subscription || ! $subscription->plan) {
            return response()->json([
                'ok' => false,
                'error' => 'subscription_not_found',
            ], 404);
        }

        $status = (string) $subscription->status;
        $isActive = $status === 'Ativa';

        $plan = $subscription->plan;
        $limits = $this->parsePlanEntitlements((string) ($plan->recursos ?? ''));
        [$periodStart, $periodEnd] = $this->resolveEntitlementPeriod($subscription, (string) $plan->ciclo_cobranca);

        return response()->json([
            'ok' => true,
            'project_id' => $data['project_id'],
            'subscription' => [
                'id' => $subscription->id,
                'company_id' => $subscription->company_id,
                'license_id' => $subscription->license_id,
                'product' => $plan->product?->nome,
                'plan_code' => $plan->nome,
                'status' => $isActive ? 'active' : 'inactive',
                'source_status' => $status,
                'starts_at' => $periodStart,
                'ends_at' => $periodEnd,
            ],
            'balances' => $isActive ? $limits : [],
        ]);
    }

    private function parsePlanEntitlements(string $resources): array
    {
        $resources = trim($resources);
        if ($resources === '') {
            return [];
        }

        $allowed = ['content_credit', 'video_second', 'avatar_second'];
        $decoded = json_decode($resources, true);
        $raw = is_array($decoded) ? $decoded : [];

        if (! is_array($decoded)) {
            foreach (preg_split('/\R+/', $resources) ?: [] as $line) {
                $line = trim((string) $line);
                if ($line === '' || ! preg_match('/^([a-z_]+)\s*[:=]\s*([0-9]+(?:\.[0-9]+)?)$/i', $line, $matches)) {
                    continue;
                }

                $raw[strtolower($matches[1])] = (float) $matches[2];
            }
        }

        $limits = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $raw) && is_numeric($raw[$key]) && (float) $raw[$key] >= 0) {
                $limits[$key] = round((float) $raw[$key], 2);
            }
        }

        return $limits;
    }

    private function resolveEntitlementPeriod(Subscription $subscription, string $cycle): array
    {
        $periodEnd = $subscription->next_due_date?->copy()->startOfDay();
        $periodStart = null;

        if ($periodEnd) {
            $periodStart = match ($cycle) {
                'anual' => $periodEnd->copy()->subYear(),
                'trial' => $periodEnd->copy()->subDays(30),
                default => $periodEnd->copy()->subMonth(),
            };
        } else {
            $periodStart = $subscription->activated_at?->copy()->startOfDay() ?? now()->startOfMonth();

            $periodEnd = match ($cycle) {
                'anual' => $periodStart->copy()->addYear(),
                'trial' => $periodStart->copy()->addDays(30),
                'cortesia', 'implantacao' => null,
                default => $periodStart->copy()->addMonth(),
            };
        }

        return [
            $periodStart?->toIso8601String(),
            $periodEnd?->toIso8601String(),
        ];
    }

    private function isAuthorized(Request $request): bool
    {
        $expectedToken = trim((string) config('centro_ia.internal_token', ''));
        $receivedToken = trim((string) $request->bearerToken());

        if ($expectedToken !== '' && $receivedToken !== '' && hash_equals($expectedToken, $receivedToken)) {
            return true;
        }

        return $this->verifyServiceSignature($request);
    }

    private function verifyServiceSignature(Request $request): bool
    {
        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            return false;
        }

        $projectId = trim((string) $request->header('X-Vitrine-Project', ''));
        $timestamp = trim((string) $request->header('X-Vitrine-Timestamp', ''));
        $nonce = trim((string) $request->header('X-Vitrine-Nonce', ''));
        $signatureEncoded = trim((string) $request->header('X-Vitrine-Signature', ''));
        $algorithm = trim((string) $request->header('X-Vitrine-Signature-Alg', ''));

        if (
            $projectId === ''
            || $timestamp === ''
            || $nonce === ''
            || $signatureEncoded === ''
            || $algorithm !== 'Ed25519'
            || ! ctype_digit($timestamp)
            || strlen($nonce) !== 32
            || ! ctype_xdigit($nonce)
        ) {
            return false;
        }

        $identity = config('centro_ia.service_identities.' . $projectId);

        if (! is_array($identity)) {
            return false;
        }

        $maxClockSkew = max(30, (int) ($identity['max_clock_skew'] ?? 90));

        if (abs(now()->timestamp - (int) $timestamp) > $maxClockSkew) {
            return false;
        }

        $publicKeyUrl = trim((string) ($identity['public_key_url'] ?? ''));

        if ($publicKeyUrl === '' || ! str_starts_with($publicKeyUrl, 'https://')) {
            return false;
        }

        try {
            $publicKeyEncoded = Cache::remember(
                'centro-ia:service-public-key:' . hash('sha256', $projectId),
                3600,
                function () use ($publicKeyUrl, $projectId): ?string {
                    $response = Http::acceptJson()
                        ->timeout(5)
                        ->get($publicKeyUrl);

                    if (
                        ! $response->successful()
                        || ! $response->json('ok')
                        || ! hash_equals($projectId, trim((string) $response->json('project_id', '')))
                        || $response->json('algorithm') !== 'Ed25519'
                    ) {
                        return null;
                    }

                    $publicKey = trim((string) $response->json('public_key', ''));

                    return $publicKey !== '' ? $publicKey : null;
                }
            );

            if (! is_string($publicKeyEncoded) || $publicKeyEncoded === '') {
                return false;
            }

            $publicKey = base64_decode($publicKeyEncoded, true);
            $signature = base64_decode($signatureEncoded, true);

            if (
                ! is_string($publicKey)
                || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
                || ! is_string($signature)
                || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            ) {
                return false;
            }

            $canonical = implode("\n", [
                'POST',
                $request->getPathInfo(),
                $projectId,
                $timestamp,
                strtolower($nonce),
                hash('sha256', $request->getContent()),
            ]);

            if (! sodium_crypto_sign_verify_detached($signature, $canonical, $publicKey)) {
                return false;
            }

            return Cache::add(
                'centro-ia:service-nonce:' . hash('sha256', $projectId . '|' . strtolower($nonce)),
                true,
                120
            );
        } catch (Throwable) {
            return false;
        }
    }

    private function resolveAgent(array $config): ?AiAgent
    {
        $agentId = $config['agent_id'] ?? null;
        if ($agentId !== null && $agentId !== '') {
            $agent = AiAgent::find($agentId);
            if ($agent) {
                return $agent;
            }
        }

        $agentSlug = trim((string) ($config['agent_slug'] ?? ''));
        if ($agentSlug !== '') {
            return AiAgent::query()->where('slug', $agentSlug)->first();
        }

        return null;
    }
}
