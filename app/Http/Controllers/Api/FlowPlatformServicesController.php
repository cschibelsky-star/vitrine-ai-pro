<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FlowSecretsManagerService;
use App\Services\FlowStorageManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FlowPlatformServicesController extends Controller
{
    public function putSecret(Request $request, FlowSecretsManagerService $secrets): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'uuid' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'key' => ['required', 'string', 'max:160'],
            'value' => ['required', 'string'],
            'scope' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:30'],
            'expires_at' => ['nullable', 'date'],
            'metadata' => ['nullable', 'array'],
        ]);

        $secret = $secrets->put($payload);

        return response()->json(['ok' => true, 'secret' => $secret], $secret->wasRecentlyCreated ? 201 : 200);
    }

    public function resolveSecret(Request $request, FlowSecretsManagerService $secrets): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'key' => ['required', 'string', 'max:160'],
            'scope' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $value = $secrets->resolve($payload['key'], $payload['company_id'] ?? null, $payload['scope'] ?? 'tenant');
        } catch (Throwable $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 404);
        }

        return response()->json(['ok' => true, 'key' => $payload['key'], 'value' => $value]);
    }

    public function putStorage(Request $request, FlowStorageManagerService $storage): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'uuid' => ['nullable', 'uuid'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'workflow_uuid' => ['nullable', 'uuid'],
            'execution_id' => ['nullable', 'string', 'max:190'],
            'disk' => ['nullable', 'string', 'max:80'],
            'path' => ['required', 'string', 'max:1024'],
            'content_base64' => ['required', 'string'],
            'visibility' => ['nullable', 'in:private,public'],
            'mime_type' => ['nullable', 'string', 'max:190'],
            'metadata' => ['nullable', 'array'],
        ]);

        try {
            $object = $storage->put($payload);
        } catch (Throwable $exception) {
            return response()->json(['ok' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'object' => $object], $object->wasRecentlyCreated ? 201 : 200);
    }

    public function showStorage(Request $request, string $uuid, FlowStorageManagerService $storage): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        return response()->json(['ok' => true, 'object' => $storage->metadata($uuid)]);
    }

    public function deleteStorage(Request $request, string $uuid, FlowStorageManagerService $storage): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $storage->delete($uuid);

        return response()->json(['ok' => true]);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }
}
