<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\AI\Services\AiDevHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AiDevHubController extends Controller
{
    public function chat(Request $request, AiDevHubService $service): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'provider' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:190'],
            'profile' => ['nullable', 'in:economy,balanced,premium'],
            'system' => ['nullable', 'string', 'max:30000'],
            'prompt' => ['required', 'string', 'min:3', 'max:160000'],
            'options' => ['nullable', 'array'],
        ]);

        return $this->run(fn () => $service->chat($data));
    }

    public function compare(Request $request, AiDevHubService $service): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'provider' => ['nullable', 'string', 'max:80'],
            'models' => ['nullable', 'array', 'max:6'],
            'models.*' => ['string', 'max:190'],
            'system' => ['nullable', 'string', 'max:30000'],
            'prompt' => ['required', 'string', 'min:3', 'max:160000'],
            'options' => ['nullable', 'array'],
        ]);

        return $this->run(fn () => $service->compare($data));
    }

    public function codeReview(Request $request, AiDevHubService $service): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'provider' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:190'],
            'profile' => ['nullable', 'in:economy,balanced,premium'],
            'prompt' => ['required', 'string', 'min:3', 'max:200000'],
            'options' => ['nullable', 'array'],
        ]);

        return $this->run(fn () => $service->codeReview($data));
    }

    public function routePreview(Request $request, AiDevHubService $service): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'provider' => ['nullable', 'string', 'max:80'],
            'profile' => ['nullable', 'in:economy,balanced,premium'],
            'modality' => ['nullable', 'in:text,embedding,image,audio,transcription'],
        ]);

        return $this->run(fn () => $service->routePreview($data));
    }

    public function models(Request $request, AiDevHubService $service): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $filters = $request->validate([
            'provider' => ['nullable', 'string', 'max:80'],
            'tier' => ['nullable', 'in:economy,balanced,premium'],
            'modality' => ['nullable', 'in:text,embedding,image,audio,transcription'],
        ]);

        return response()->json([
            'ok' => true,
            'models' => $service->listModels($filters),
        ]);
    }

    public function usage(Request $request, AiDevHubService $service): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $data = $request->validate([
            'project_id' => ['nullable', 'string', 'max:120'],
        ]);

        return response()->json([
            'ok' => true,
            'usage' => $service->usageSummary($data['project_id'] ?? null),
        ]);
    }

    private function authorizeRequest(Request $request): ?JsonResponse
    {
        $expected = (string) config('ai_dev_hub.internal_token', '');
        $received = (string) $request->bearerToken();

        if ($expected === '' || $received === '' || ! hash_equals($expected, $received)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $projectId = trim((string) $request->input('project_id', $request->header('X-Vitrine-Project', '')));
        $projectHeader = trim((string) $request->header('X-Vitrine-Project', ''));

        if ($projectId !== '' && $projectHeader !== '' && ! hash_equals($projectId, $projectHeader)) {
            return response()->json(['ok' => false, 'error' => 'project_identity_mismatch'], 422);
        }

        return null;
    }

    private function run(callable $callback): JsonResponse
    {
        try {
            return response()->json(['ok' => true, 'data' => $callback()]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'ai_dev_hub_failed',
                'message' => 'Falha ao processar a solicitação no AI Dev Hub.',
            ], 502);
        }
    }
}
