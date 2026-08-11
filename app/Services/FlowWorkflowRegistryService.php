<?php

namespace App\Services;

use App\Models\FlowWorkflow;
use Illuminate\Support\Str;
use InvalidArgumentException;

class FlowWorkflowRegistryService
{
    /**
     * Registra ou atualiza a definicao canonica de um workflow do Vitrine IA Flow.
     *
     * A identidade funcional e formada por company_id + workflow_key + version.
     * O UUID e a identidade tecnica persistente e, quando informado, tem precedencia.
     */
    public function register(array $data): FlowWorkflow
    {
        $this->validateIdentity($data);

        $uuid = $data['uuid'] ?? null;
        unset($data['uuid']);

        $workflow = $uuid
            ? FlowWorkflow::query()->firstOrNew(['uuid' => $uuid])
            : FlowWorkflow::query()->firstOrNew([
                'company_id' => $data['company_id'] ?? null,
                'workflow_key' => $data['workflow_key'],
                'version' => $data['version'],
            ]);

        if (! $workflow->exists && ! $workflow->uuid) {
            $workflow->uuid = (string) Str::uuid();
        }

        $workflow->fill($this->normalize($data));
        $workflow->save();

        // Mantem wasRecentlyCreated no mesmo objeto para o controller responder 201/200 corretamente.
        return $workflow->refresh();
    }

    public function normalize(array $data): array
    {
        $metadata = $data['metadata'] ?? [];

        if (! is_array($metadata)) {
            $metadata = [];
        }

        $metadata['registry'] = array_merge(
            [
                'source' => 'vitrine-ai-pro-core',
                'executor' => $data['n8n_workflow_id'] ?? null ? 'n8n' : ($metadata['registry']['executor'] ?? 'internal'),
            ],
            is_array($metadata['registry'] ?? null) ? $metadata['registry'] : [],
        );

        $data['metadata'] = $metadata;

        return $data;
    }

    private function validateIdentity(array $data): void
    {
        if (! empty($data['uuid'])) {
            return;
        }

        foreach (['workflow_key', 'version'] as $field) {
            if (! isset($data[$field]) || trim((string) $data[$field]) === '') {
                throw new InvalidArgumentException("Campo obrigatorio ausente para registro canonico: {$field}.");
            }
        }
    }
}
