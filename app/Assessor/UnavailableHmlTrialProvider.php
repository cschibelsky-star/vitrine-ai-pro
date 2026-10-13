<?php
namespace App\Assessor;
use RuntimeException;
final class UnavailableHmlTrialProvider implements HmlTrialProvider
{
    public function provision(string $tenantId, int $companyId, string $hostname): array
    { throw new RuntimeException('official_hml_trial_adapter_not_configured'); }
    public function revoke(string $tenantId): void
    { throw new RuntimeException('official_hml_trial_adapter_not_configured'); }
}
