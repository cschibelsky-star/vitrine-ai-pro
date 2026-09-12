<?php

declare(strict_types=1);

namespace App\Factory\Approval\Services;

use Illuminate\Support\Facades\Crypt;
use RuntimeException;
use Throwable;

final class ApprovalTokenService
{
    private const TTL_MINUTES = 30;

    public function issue(string $scope, string $fingerprint, array $context = []): string
    {
        $now = now();
        $payload = [
            'scope' => $scope,
            'fingerprint' => $fingerprint,
            'context' => $context,
            'issued_at' => $now->timestamp,
            'expires_at' => $now->copy()->addMinutes(self::TTL_MINUTES)->timestamp,
        ];

        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function assertValid(?string $token, string $scope, string $fingerprint): array
    {
        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException('approval_token_required');
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new RuntimeException('invalid_approval_token', 0, $exception);
        }

        if (! is_array($payload)) {
            throw new RuntimeException('invalid_approval_token');
        }

        $tokenScope = (string) ($payload['scope'] ?? '');
        $tokenFingerprint = (string) ($payload['fingerprint'] ?? '');

        if (! hash_equals($scope, $tokenScope) || ! hash_equals($fingerprint, $tokenFingerprint)) {
            throw new RuntimeException('approval_token_scope_mismatch');
        }

        if ((int) ($payload['expires_at'] ?? 0) < now()->timestamp) {
            throw new RuntimeException('approval_token_expired');
        }

        return $payload;
    }
}
