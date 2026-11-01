<?php
declare(strict_types=1);
require_once __DIR__ . '/IndoorServiceAuth.php';
require_once __DIR__ . '/IndoorRequestNonceStore.php';

/**
 * Authenticate a private request against server-owned service credentials.
 * No HTTP routing, secrets in code, network or database migrations.
 */
final class IndoorServiceGateway
{
    /**
     * @param array<string,array{secret:string,tenant_id:string,enabled:bool,allowed_path:string}> $credentials
     * @return array{service_id:string,tenant_id:string}|null
     */
    public static function authorize(
        array $credentials, PDO $db, string $serviceId,
        string $method, string $path, string $body,
        int $timestamp, string $nonce, string $signature,
        ?int $now = null
    ): ?array {
        if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $serviceId)) return null;
        $credential = $credentials[$serviceId] ?? null;
        if (!is_array($credential) || ($credential['enabled'] ?? false) !== true) return null;
        $tenantId = $credential['tenant_id'] ?? null;
        if (!is_string($tenantId) || !preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $tenantId)) return null;
        if ($method !== 'POST' || $path !== ($credential['allowed_path'] ?? null)) return null;
        $secret = $credential['secret'] ?? null;
        if (!is_string($secret) || strlen($secret) < 32) return null;
        $store = new IndoorRequestNonceStore($db, $serviceId);
        $valid = IndoorServiceAuth::verify(
            $secret, $method, $path, $body, $timestamp, $nonce, $signature,
            static fn(string $hash, int $time): bool => $store->claim($hash, $time),
            $now
        );
        return $valid ? ['service_id' => $serviceId, 'tenant_id' => $tenantId] : null;
    }
}
