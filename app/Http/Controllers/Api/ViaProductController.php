<?php

namespace App\Http\Controllers\Api;

use App\Factory\AI\Via\Products\Procurement\ProcurementViaService;
use App\Factory\AI\Via\Products\SocialMedia\SocialMediaViaService;
use App\Factory\AI\Via\Products\TvDigital\TvDigitalViaService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ViaProductController extends Controller
{
    public function socialMedia(Request $request, SocialMediaViaService $service): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'briefing' => ['required', 'array'],
            'context' => ['nullable', 'array'],
        ]);

        return $this->respond($service->generate(
            $payload['briefing'],
            $payload['context'] ?? [],
        ));
    }

    public function procurement(Request $request, ProcurementViaService $service): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'task' => ['required', 'string', 'max:80', Rule::in(ProcurementViaService::supportedTasks())],
            'input' => ['required', 'array'],
            'context' => ['nullable', 'array'],
        ]);

        return $this->respond($service->execute(
            $payload['task'],
            $payload['input'],
            $payload['context'] ?? [],
        ));
    }

    public function tvDigital(Request $request, TvDigitalViaService $service): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'task' => ['required', 'string', 'max:80', Rule::in(TvDigitalViaService::supportedTasks())],
            'input' => ['required', 'array'],
            'context' => ['nullable', 'array'],
        ]);

        return $this->respond($service->execute(
            $payload['task'],
            $payload['input'],
            $payload['context'] ?? [],
        ));
    }

    private function respond(array $result): JsonResponse
    {
        return response()->json($result, ($result['ok'] ?? false) ? 200 : 503);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }
}
