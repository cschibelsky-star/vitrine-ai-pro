<?php

declare(strict_types=1);

namespace Tests\Unit\Factory;

use App\Factory\Production\Services\CanonicalRouteGate;
use PHPUnit\Framework\TestCase;

final class CanonicalRouteGateTest extends TestCase
{
    private function valid(): array
    {
        return [
            'project_id' => 'example', 'environment' => 'homologation',
            'hostname' => 'example.hml.example.com', 'route_status' => 'active',
            'provider_verified' => true, 'registry_verified' => true,
            'dns_verified' => true, 'proxy_verified' => true,
            'tls_verified' => true, 'upstream_verified' => true,
            'http_verified' => true, 'duplicate_route' => false, 'hostname_conflict' => false,
        ];
    }

    public function test_verified_hml_is_allowed(): void
    {
        self::assertTrue((new CanonicalRouteGate())->evaluate($this->valid())['allowed']);
    }

    public function test_missing_dns_blocks(): void
    {
        $deployment = $this->valid();
        $deployment['dns_verified'] = false;
        self::assertContains('unverified_dns_verified', (new CanonicalRouteGate())->evaluate($deployment)['reasons']);
    }

    public function test_planned_route_blocks(): void
    {
        $deployment = $this->valid();
        $deployment['route_status'] = 'planned';
        self::assertFalse((new CanonicalRouteGate())->evaluate($deployment)['allowed']);
    }

    public function test_duplicate_route_blocks(): void
    {
        $deployment = $this->valid();
        $deployment['duplicate_route'] = true;
        self::assertFalse((new CanonicalRouteGate())->evaluate($deployment)['allowed']);
    }

    public function test_unknown_conflict_evidence_blocks(): void
    {
        $deployment = $this->valid();
        unset($deployment['duplicate_route']);
        self::assertFalse((new CanonicalRouteGate())->evaluate($deployment)['allowed']);
    }

    public function test_production_requires_two_approvals(): void
    {
        $deployment = $this->valid();
        $deployment['environment'] = 'production';
        self::assertContains('production_approval_required', (new CanonicalRouteGate())->evaluate($deployment)['reasons']);
        $deployment['hml_approved'] = true;
        $deployment['cockpit_approved'] = true;
        self::assertTrue((new CanonicalRouteGate())->evaluate($deployment)['allowed']);
    }
}
