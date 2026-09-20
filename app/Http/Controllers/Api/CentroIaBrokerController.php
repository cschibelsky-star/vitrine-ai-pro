<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Services\Ai\AiExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class CentroIaBrokerController extends Controller
{
    public function execute(Request $request, AiExecutionService $service): JsonResponse
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

        $execution = $service->execute($agent, $prompt);
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
