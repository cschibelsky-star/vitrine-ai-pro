<?php

namespace Tests\Unit\Marketing;

use App\Marketing\Domain\Contracts\CommonEnvelope;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CommonEnvelopeTest extends TestCase
{
    public function test_envelope_maps_tenant_to_company_and_denies_sensitive_permissions(): void
    {
        $envelope = new CommonEnvelope(
            schemaVersion: '1.0.0',
            companyId: 1,
            campaignId: 'CAM-001',
            taskId: 'TASK-001',
            agentId: 'campaign_planner',
            attempt: 1,
            language: 'pt-BR',
            dependencies: ['STRATEGY-001'],
            inputRefs: ['strategy:1'],
            payload: ['objective' => 'Gerar leads'],
        );

        $data = $envelope->toArray();

        $this->assertSame(1, $data['tenant_id']);
        $this->assertSame(1, $data['company_id']);
        $this->assertFalse($data['constraints']['may_publish']);
        $this->assertFalse($data['constraints']['may_spend']);
    }

    public function test_envelope_rejects_publish_permission(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CommonEnvelope(
            schemaVersion: '1.0.0',
            companyId: 1,
            campaignId: 'CAM-001',
            taskId: 'TASK-001',
            agentId: 'social_distribution',
            attempt: 1,
            language: 'pt-BR',
            dependencies: [],
            inputRefs: [],
            payload: [],
            mayPublish: true,
        );
    }
}
