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
    public function execute(Request $request, AiRoutingService $service): JsonResponse
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

        if ($routingCapability === 'image_generation') {
            return $this->executeRoteiaImageFallback((string) $data['project_id'], $capability, $prompt);
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

    private function executeRoteiaImageFallback(string $projectId, string $capability, string $prompt): JsonResponse
    {
        $apiKey = trim((string) env('ROTEIA_API_KEY', ''));
        $baseUrl = rtrim(trim((string) env('ROTEIA_BASE_URL', '')), '/');

        if ($apiKey === '' || $baseUrl === '') {
            return response()->json([
                'ok' => false,
                'error' => 'roteia_runtime_not_configured',
            ], 503);
        }

        $model = 'google/gemini-3.1-flash-image';
        $apiBaseUrl = str_ends_with($baseUrl, '/v1') ? $baseUrl : $baseUrl.'/v1';
        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(120)
            ->retry(1, 300, throw: false)
            ->post($apiBaseUrl.'/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
            ]);

        if (! $response->successful()) {
            return response()->json([
                'ok' => false,
                'error' => 'roteia_image_generation_failed',
                'provider_status' => $response->status(),
            ], 502);
        }

        $payload = (array) $response->json();
        $assetUrl = data_get($payload, 'data.0.url')
            ?? data_get($payload, 'asset_url')
            ?? data_get($payload, 'url');
        $assetBase64 = data_get($payload, 'data.0.b64_json')
            ?? data_get($payload, 'output_image.data')
            ?? data_get($payload, 'image_base64');

        if ((! is_string($assetUrl) || trim($assetUrl) === '') && (! is_string($assetBase64) || trim($assetBase64) === '')) {
            return response()->json([
                'ok' => false,
                'error' => 'roteia_image_payload_missing',
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'project_id' => $projectId,
            'capability' => $capability,
            'model' => $model,
            'media_status' => 'completed',
            'asset_url' => is_string($assetUrl) ? $assetUrl : null,
            'asset_base64' => is_string($assetBase64) ? $assetBase64 : null,
            'provider' => 'roteia',
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
