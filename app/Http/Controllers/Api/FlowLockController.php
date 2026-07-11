<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class FlowLockController extends Controller
{
    public function acquire(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'key' => ['required', 'string', 'max:190'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'ttl' => ['nullable', 'integer', 'between:1,3600'],
            'owner' => ['nullable', 'string', 'max:190'],
            'metadata' => ['nullable', 'array'],
        ]);

        $owner = $payload['owner'] ?? (string) Str::uuid();
        $ttl = (int) ($payload['ttl'] ?? 120);
        $name = $this->lockName($payload['key'], $payload['company_id'] ?? null);
        $lock = Cache::lock($name, $ttl, $owner);
        $acquired = $lock->get();

        return response()->json([
            'ok' => $acquired,
            'acquired' => $acquired,
            'lock' => [
                'key' => $payload['key'],
                'company_id' => $payload['company_id'] ?? null,
                'owner' => $acquired ? $owner : null,
                'ttl' => $ttl,
                'expires_at' => $acquired ? now()->addSeconds($ttl)->toIso8601String() : null,
            ],
        ], $acquired ? 201 : 409);
    }

    public function release(Request $request): JsonResponse
    {
        if (! $this->authorized($request)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'key' => ['required', 'string', 'max:190'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'owner' => ['required', 'string', 'max:190'],
        ]);

        $name = $this->lockName($payload['key'], $payload['company_id'] ?? null);
        $released = Cache::restoreLock($name, $payload['owner'])->release();

        return response()->json([
            'ok' => $released,
            'released' => $released,
            'lock' => [
                'key' => $payload['key'],
                'company_id' => $payload['company_id'] ?? null,
            ],
        ], $released ? 200 : 409);
    }

    private function authorized(Request $request): bool
    {
        $expected = (string) config('vitrine_flow.token');
        $received = (string) $request->bearerToken();

        return $expected !== '' && hash_equals($expected, $received);
    }

    private function lockName(string $key, ?int $companyId): string
    {
        return sprintf(
            'vitrine-flow:lock:company:%s:%s',
            $companyId ?? 'global',
            hash('sha256', $key),
        );
    }
}
