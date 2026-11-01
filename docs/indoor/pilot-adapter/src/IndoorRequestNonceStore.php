<?php
declare(strict_types=1);

/**
 * Atomic replay protection for authenticated Indoor service requests.
 * Caller MUST supply a PDO connection to a SHARED persistent database.
 * Schema: see indoor_request_nonces.sql. No automatic migration.
 */
final class IndoorRequestNonceStore
{
    private PDO $db;
    private string $serviceId;

    public function __construct(PDO $db, string $serviceId)
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,100}$/D', $serviceId)) {
            throw new InvalidArgumentException('Invalid service identity');
        }
        $this->db = $db;
        $this->serviceId = $serviceId;
    }

    /** Atomic insert; database unique key rejects reuse across workers. */
    public function claim(string $nonceHash, int $timestamp): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $nonceHash)) return false;
        try {
            $query = $this->db->prepare(
                'INSERT INTO indoor_request_nonces (service_id, nonce_hash, expires_at) VALUES (?, ?, ?)'
            );
            return $query->execute([$this->serviceId, $nonceHash, time() + 300]);
        } catch (PDOException $error) {
            // SQLSTATE 23xxx denotes integrity-constraint violation.
            // Do not swallow storage failures (fail request closed).
            if (strncmp((string) $error->getCode(), '23', 2) === 0) return false;
            throw $error;
        }
    }
}
