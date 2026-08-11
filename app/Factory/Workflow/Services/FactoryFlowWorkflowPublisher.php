<?php

declare(strict_types=1);

namespace App\Factory\Workflow\Services;

use App\Models\FlowWorkflow;
use App\Services\FlowWorkflowRegistryService;
use Illuminate\Support\Str;

class FactoryFlowWorkflowPublisher
{
    public function __construct(
        private readonly WorkflowDesigner $designer,
        private readonly FlowWorkflowRegistryService $registry,
    ) {
    }

    /**
     * Publica no Core a definicao canonica de um workflow desenhado pela Factory.
     * A Factory apenas descreve e registra; nunca executa o n8n diretamente.
     */
    public function publish(
        string $domain,
        string $version = '1.0.0',
        ?int $companyId = null,
        array $overrides = [],
    ): FlowWorkflow {
        return $this->registry->register(
            array_replace_recursive(
                $this->payloadForDomain($domain, $version, $companyId),
                $overrides,
            ),
        );
    }

    public function payloadForDomain(
        string $domain,
        string $version = '1.0.0',
        ?int $companyId = null,
    ): array {
        $design = $this->designer->design($domain);
        $workflowKey = Str::of($domain)
            ->lower()
            ->ascii()
            ->slug('_')
            ->prepend('factory_')
            ->toString();

        return [
            'company_id' => $companyId,
            'workflow_key' => $workflowKey,
            'name' => 'Factory - '.$domain,
            'version' => $version,
            'category' => 'factory',
            'owner' => 'vitrine-ai-pro-factory',
            'queue' => 'factory',
            'priority' => 50,
            'compatibility' => [
                'source' => 'factory-workflow-designer',
                'domain' => $domain,
            ],
            'metadata' => [
                'factory' => [
                    'domain' => $design['domain'],
                    'steps' => $design['steps'],
                    'transitions' => $design['transitions'],
                    'designed_at' => $design['designed_at'],
                ],
                'registry' => [
                    'source' => 'vitrine-ai-pro-factory',
                    'executor' => 'internal',
                ],
            ],
            'status' => 'draft',
            'is_active' => false,
        ];
    }
}
