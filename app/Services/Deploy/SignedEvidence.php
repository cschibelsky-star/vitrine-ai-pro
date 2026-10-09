<?php

declare(strict_types=1);

namespace App\Services\Deploy;

use RuntimeException;

/** Only the operational collector holds the private signing key. */
final class SignedEvidence
{
    public function __construct(private string $directory, private string $publicKey, private int $ttl = 900) {}

    public function read(string $project): array
    {
        if (!preg_match('/^[a-z0-9-]{1,80}$/D', $project)) {
            throw new RuntimeException('invalid_project');
        }
        $key = base64_decode($this->publicKey, true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new RuntimeException('collector_key_not_configured');
        }
        $envelope = json_decode((string) file_get_contents($this->directory.'/'.$project.'.json'), true, 32, JSON_THROW_ON_ERROR);
        $payload = base64_decode($envelope['payload'] ?? '', true);
        $signature = base64_decode($envelope['signature'] ?? '', true);
        if ($payload === false || $signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || !sodium_crypto_sign_verify_detached($signature, $payload, $key)) {
            throw new RuntimeException('invalid_collector_signature');
        }
        $record = json_decode($payload, true, 32, JSON_THROW_ON_ERROR);
        $at = $record['observed_at'] ?? 0;
        if (!is_int($at) || $at > time() + 30 || $at < time() - $this->ttl || ($record['project_id'] ?? null) !== $project) {
            throw new RuntimeException('stale_or_wrong_evidence');
        }
        $record['digest'] = hash('sha256', $payload);
        return $record;
    }
}
