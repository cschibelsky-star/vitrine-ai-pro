<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Factory\Workflow\Services\FactoryFlowWorkflowPublisher;
use App\Factory\Workflow\Services\WorkflowDesigner;
use App\Services\FlowWorkflowRegistryService;
use PHPUnit\Framework\TestCase;

class FactoryFlowWorkflowPublisherTest extends TestCase
{
    public function test_factory_generates_canonical_payload_without_n8n_execution(): void
    {
        $publisher = new FactoryFlowWorkflowPublisher(
            new WorkflowDesigner(),
            new FlowWorkflowRegistryService(),
        );

        $payload = $publisher->payloadForDomain('Compras Publicas', '2.0.0', 15);

        $this->assertSame(15, $payload['company_id']);
        $this->assertSame('factory_compras_publicas', $payload['workflow_key']);
        $this->assertSame('2.0.0', $payload['version']);
        $this->assertSame('factory', $payload['category']);
        $this->assertSame('vitrine-ai-pro-factory', $payload['metadata']['registry']['source']);
        $this->assertSame('internal', $payload['metadata']['registry']['executor']);
        $this->assertFalse($payload['is_active']);
        $this->assertArrayNotHasKey('n8n_workflow_id', $payload);
        $this->assertContains('analise_documental', $payload['metadata']['factory']['steps']);
        $this->assertNotEmpty($payload['metadata']['factory']['transitions']);
    }
}
