<?php

namespace App\Services\Deploy;

/**
 * Fail-closed preflight for production release orchestration.
 * This method MUST be called server-side by the real deploy executor;
 * the Cockpit badge alone is not an authorization.
 */
final class HomologationGate
{
    public function evaluate(array $release): array
    {
        $required = [
            'hml_url', 'production_url', 'hml_dns_ok', 'hml_tls_ok',
            'hml_health_ok', 'isolated_runtime', 'isolated_data',
            'ci_green', 'tests_passed', 'backup_verified',
            'rollback_ready', 'approved_by_authorized_user',
            'approval_evidence_id', 'tested_sha', 'target_sha',
        ];
        $blockers = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $release) || $release[$field] === null || $release[$field] === '') {
                $blockers[] = "missing:{$field}";
            }
        }
        foreach (['hml_dns_ok','hml_tls_ok','hml_health_ok','isolated_runtime','isolated_data','ci_green','tests_passed','backup_verified','rollback_ready','approved_by_authorized_user'] as $field) {
            if (($release[$field] ?? null) !== true) {
                $blockers[] = "not_verified:{$field}";
            }
        }
        foreach (['hml_url', 'production_url'] as $field) {
            $url = $release[$field] ?? null;
            if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
                $blockers[] = "invalid_https_url:{$field}";
            }
        }
        if (($release['hml_url'] ?? null) === ($release['production_url'] ?? null)) {
            $blockers[] = 'same_url';
        }
        $tested = $release['tested_sha'] ?? null;
        $target = $release['target_sha'] ?? null;
        if (!is_string($tested) || !preg_match('/^[a-f0-9]{40}$/i', $tested) || $tested !== $target) {
            $blockers[] = 'sha_not_approved';
        }
        return ['allowed' => count($blockers) === 0, 'blockers' => array_values(array_unique($blockers))];
    }
}
