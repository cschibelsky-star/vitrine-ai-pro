<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FlowWorkflowRegistryService;
use PHPUnit\Framework\TestCase;

class FlowWorkflowRegistryServiceTest extends TestCase
{
    public function test_normalize_marks_n8n_executor_when_workflow_id_is_present(): void
    {
        $service = new FlowWorkflowRegistryService();

        $data = $service->normalize([
            'workflow_key' => 'provision_product',
            'version' => '1.0.0',
            'n8n_workflow_id' => 'n8n-123',
        ]);

        $this->assertSame('vitrine-ai-pro-core', $data['metadata']['registry']['source']);
        $this->assertSame('n8n', $data['metadata']['registry']['executor']);
    }

    public function test_normalize_preserves_explicit_registry_metadata(): void
    {
        $service = new FlowWorkflowRegistryService();

        $data = $service->normalize([
            'workflow_key' => 'factory_guia_turismo',
            'version' => '1.0.0',
            'metadata' => [
                'registry' => [
                    'source' => 'vitrine-ai-pro-factory',
                    'executor' => 'internal',
                ],
            ],
        ]);

        $this->assertSame('vitrine-ai-pro-factory', $data['metadata']['registry']['source']);
        $this->assertSame('internal', $data['metadata']['registry']['executor']);
    }
}
