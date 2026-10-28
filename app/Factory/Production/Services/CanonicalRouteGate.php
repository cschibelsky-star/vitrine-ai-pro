<?php

declare(strict_types=1);

namespace App\Factory\Production\Services;

/**
 * Fail-closed reconciliation gate for Factory/Provider deployments.
 * This component does not mutate DNS, proxy, Docker or production.
 */
final class CanonicalRouteGate
{
    public function evaluate(array $deployment): array
    {
        $required = ['project_id', 'environment', 'hostname', 'provider_verified', 'registry_verified',
            'dns_verified', 'proxy_verified', 'tls_verified', 'upstream_verified', 'http_verified'];
        $reasons = [];
        foreach ($required as $field) {
            if (! array_key_exists($field, $deployment) || $deployment[$field] === null || $deployment[$field] === '') {
                $reasons[] = 'missing_' . $field;
            }
        }
        foreach (['provider_verified', 'registry_verified', 'dns_verified', 'proxy_verified',
            'tls_verified', 'upstream_verified', 'http_verified'] as $field) {
            if (($deployment[$field] ?? null) !== true) {
                $reasons[] = 'unverified_' . $field;
            }
        }
        if (! in_array(($deployment['environment'] ?? null), ['homologation', 'production'], true)) {
            $reasons[] = 'invalid_environment';
        }
        if (in_array(($deployment['route_status'] ?? null), ['planned', 'draft'], true)) {
            $reasons[] = 'route_not_publishable';
        }
        if (($deployment['duplicate_route'] ?? false) !== false) {
            $reasons[] = 'duplicate_route';
        }
        if (($deployment['hostname_conflict'] ?? false) !== false) {
            $reasons[] = 'hostname_conflict';
        }
        if (($deployment['environment'] ?? null) === 'production' &&
            (($deployment['hml_approved'] ?? false) !== true || ($deployment['cockpit_approved'] ?? false) !== true)) {
            $reasons[] = 'production_approval_required';
        }
        $reasons = array_values(array_unique($reasons));

        return [
            'allowed' => $reasons === [],
            'status' => $reasons === [] ? 'verified' : 'blocked',
            'reasons' => $reasons,
            'project_id' => $deployment['project_id'] ?? null,
            'hostname' => $deployment['hostname'] ?? null,
            'environment' => $deployment['environment'] ?? null,
        ];
    }
}
