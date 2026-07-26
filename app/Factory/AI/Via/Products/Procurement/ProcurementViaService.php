<?php

namespace App\Factory\AI\Via\Products\Procurement;

use App\Factory\AI\Via\Services\ViaOrchestrator;
use InvalidArgumentException;

class ProcurementViaService
{
    private const SUPPORTED_TASKS = [
        'pending_issue_analysis',
        'document_generation',
        'document_review',
        'process_guidance',
    ];

    public function __construct(
        private readonly ViaOrchestrator $via,
    ) {
    }

    public static function supportedTasks(): array
    {
        return self::SUPPORTED_TASKS;
    }

    public function execute(string $task, array $input, array $context = []): array
    {
        if (! in_array($task, self::SUPPORTED_TASKS, true)) {
            throw new InvalidArgumentException("Unsupported procurement task: {$task}");
        }

        $request = [
            'task' => $task,
            'domain' => 'public_procurement',
            'input' => [
                'process_number' => $input['process_number'] ?? null,
                'document_type' => $input['document_type'] ?? null,
                'source_text' => $input['source_text'] ?? null,
                'pending_issue' => $input['pending_issue'] ?? null,
                'responsible_sector' => $input['responsible_sector'] ?? null,
                'deadline' => $input['deadline'] ?? null,
                'legal_basis' => $input['legal_basis'] ?? null,
                'additional_context' => $input['additional_context'] ?? null,
            ],
            'output' => [
                'language' => $input['language'] ?? 'pt-BR',
                'plain_language_explanation' => (bool) ($input['plain_language_explanation'] ?? true),
                'include_checklist' => (bool) ($input['include_checklist'] ?? true),
                'include_required_documents' => (bool) ($input['include_required_documents'] ?? true),
                'include_responsible_sector' => (bool) ($input['include_responsible_sector'] ?? true),
                'include_deadline_guidance' => (bool) ($input['include_deadline_guidance'] ?? true),
            ],
        ];

        return $this->via->handle($request, array_merge($context, [
            'product' => 'agente-compras-ia',
            'metadata' => array_merge(
                $context['metadata'] ?? [],
                [
                    'module' => 'procurement',
                    'task' => $task,
                    'document_type' => $request['input']['document_type'],
                ],
            ),
        ]));
    }

    public function analyzePendingIssue(array $input, array $context = []): array
    {
        return $this->execute('pending_issue_analysis', $input, $context);
    }

    public function generateDocument(array $input, array $context = []): array
    {
        return $this->execute('document_generation', $input, $context);
    }
}
