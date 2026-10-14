<?php
namespace App\Assessor;
interface HmlTrialProvider
{
    /** Idempotent by tenant_id; no citizen/contact data; authenticated server-side receipt. */
    public function provision(string $tenantId, int $companyId, string $hostname): array;
    /** Idempotent revocation. Must not delete data or claim success before access is revoked. */
    public function revoke(string $tenantId): void;
}
