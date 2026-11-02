<?php
declare(strict_types=1);

namespace App\AssessorOnline\Audesp;

use InvalidArgumentException;

/**
 * Fail-closed local applicability gate for TCESP AUDESP Nova Fase IV.
 *
 * Does not produce an official JSON payload, authenticate, transmit, or
 * assume an agency's registration status. Requires authoritative input.
 * Reference: TCESP Comunicado SDG 050/2026 (08/10/2026).
 */
final class SubmissionPreflight
{
    private const START_DATE = '2026-11-03';
    private const MIN_UFESP = 2500.0;

    /**
     * @param array<string,mixed> $data
     * @return array{eligible:bool,reason:string,requires_origin:bool}
     */
    public function evaluate(array $data): array
    {
        $required = [
            'jurisdiction', 'origin_estimated_ufesp', 'origin_started_at',
            'stage', 'origin_already_reported_in_legacy_engineering',
            'state_contracts_gov_integrated',
        ];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data)) {
                return $this->deny('missing_' . $field);
            }
        }

        if (!in_array($data['jurisdiction'], ['municipal', 'state'], true)
            || !in_array($data['stage'], ['edital', 'licitacao', 'ata', 'ajuste'], true)
            || !is_bool($data['origin_already_reported_in_legacy_engineering'])
            || !is_bool($data['state_contracts_gov_integrated'])
            || !is_numeric($data['origin_estimated_ufesp'])
            || !is_string($data['origin_started_at'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $data['origin_started_at'])
            || !checkdate(
                (int) substr($data['origin_started_at'], 5, 2),
                (int) substr($data['origin_started_at'], 8, 2),
                (int) substr($data['origin_started_at'], 0, 4)
            )) {
            return $this->deny('invalid_input');
        }

        if ($data['origin_already_reported_in_legacy_engineering']) {
            return $this->deny('legacy_engineering_remains_in_legacy_system');
        }
        if ((float) $data['origin_estimated_ufesp'] < self::MIN_UFESP) {
            return $this->deny('below_origin_threshold');
        }
        if ($data['jurisdiction'] === 'state' && $data['state_contracts_gov_integrated']) {
            return $this->deny('verify_automatic_contracts_gov_route');
        }
        if ($data['origin_started_at'] < self::START_DATE) {
            // Retrospective stock has separate eligibility and cutover rules.
            // A generic real-time sender must not assume it may transmit.
            return $this->deny('requires_retrospective_stock_review');
        }

        if (in_array($data['stage'], ['ata', 'ajuste'], true)) {
            if (($data['origin_transmitted'] ?? null) !== true) {
                return $this->deny('origin_transmission_not_confirmed', true);
            }
        }

        return ['eligible' => true, 'reason' => 'preflight_only', 'requires_origin' => false];
    }

    /** @return array{eligible:bool,reason:string,requires_origin:bool} */
    private function deny(string $reason, bool $requiresOrigin = false): array
    {
        return ['eligible' => false, 'reason' => $reason, 'requires_origin' => $requiresOrigin];
    }
}
