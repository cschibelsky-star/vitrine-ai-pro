<?php

namespace App\Services;

use App\Models\FlowSecret;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use RuntimeException;

class FlowSecretsManagerService
{
    public function put(array $data): FlowSecret
    {
        return FlowSecret::updateOrCreate(
            [
                'company_id' => $data['company_id'] ?? null,
                'key' => $data['key'],
                'scope' => $data['scope'] ?? 'tenant',
            ],
            [
                'uuid' => $data['uuid'] ?? (string) Str::uuid(),
                'encrypted_value' => Crypt::encryptString($data['value']),
                'status' => $data['status'] ?? 'active',
                'expires_at' => $data['expires_at'] ?? null,
                'metadata' => $data['metadata'] ?? null,
            ],
        );
    }

    public function resolve(string $key, ?int $companyId = null, string $scope = 'tenant'): string
    {
        $secret = FlowSecret::query()
            ->where('key', $key)
            ->where('scope', $scope)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) use ($companyId) {
                if ($companyId !== null) {
                    $query->where('company_id', $companyId)->orWhereNull('company_id');
                } else {
                    $query->whereNull('company_id');
                }
            })
            ->orderByRaw('company_id is null')
            ->first();

        if (! $secret) {
            throw new RuntimeException('Secret não encontrado ou indisponível.');
        }

        $secret->forceFill(['last_accessed_at' => now()])->save();

        return Crypt::decryptString($secret->encrypted_value);
    }
}
