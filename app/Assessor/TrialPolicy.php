<?php
namespace App\Assessor;
use DateTimeImmutable;
use DateTimeZone;
use DomainException;
final class TrialPolicy
{
    public static function host(string $id): string
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $id)) {
            throw new DomainException('invalid_tenant');
        }
        return 'ao-'.$id.'.hml.vitrineiapro.com.br';
    }
    public static function receipt(array $r, string $id, int $companyId): void
    {
        foreach (['dns_valid','tls_valid','healthy','isolated','cockpit_registered','access_ready'] as $gate) {
            if (($r[$gate] ?? null) !== true) { throw new DomainException('gate_'.$gate); }
        }
        if (($r['environment'] ?? null) !== 'hml' || ($r['tenant_id'] ?? null) !== $id
            || ($r['company_id'] ?? null) !== $companyId || ($r['hostname'] ?? null) !== self::host($id)
            || !is_string($r['route_id'] ?? null) || $r['route_id'] === '') {
            throw new DomainException('receipt_mismatch');
        }
    }
    public static function dates(DateTimeImmutable $now): array
    {
        $start = $now->setTimezone(new DateTimeZone('UTC'));
        return [$start, $start->modify('+30 days')];
    }
    public static function accessible(string $status, ?DateTimeImmutable $end, DateTimeImmutable $now): bool
    {
        return $status === 'active' && $end !== null && $now < $end;
    }
}
