<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Contracts;

use InvalidArgumentException;

final readonly class CommonEnvelope
{
    /** @param list<string> $dependencies @param list<string> $inputRefs @param array<string, mixed> $payload */
    public function __construct(
        public string $schemaVersion,
        public int $companyId,
        public string $campaignId,
        public string $taskId,
        public string $agentId,
        public int $attempt,
        public string $language,
        public array $dependencies,
        public array $inputRefs,
        public array $payload,
        public bool $mayPublish = false,
        public bool $maySpend = false,
        public bool $mayInventFacts = false,
    ) {
        if ($companyId < 1) {
            throw new InvalidArgumentException('company_id must identify an existing tenant company.');
        }

        if ($campaignId === '' || $taskId === '' || $agentId === '') {
            throw new InvalidArgumentException('campaign_id, task_id and agent_id are required.');
        }

        if ($attempt < 1) {
            throw new InvalidArgumentException('attempt must be greater than zero.');
        }

        if ($mayPublish || $maySpend || $mayInventFacts) {
            throw new InvalidArgumentException('V1 envelopes cannot publish, spend or invent facts.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schema_version' => $this->schemaVersion,
            'tenant_id' => $this->companyId,
            'company_id' => $this->companyId,
            'campaign_id' => $this->campaignId,
            'task_id' => $this->taskId,
            'agent_id' => $this->agentId,
            'attempt' => $this->attempt,
            'language' => $this->language,
            'dependencies' => $this->dependencies,
            'input_refs' => $this->inputRefs,
            'constraints' => [
                'may_publish' => $this->mayPublish,
                'may_spend' => $this->maySpend,
                'may_invent_facts' => $this->mayInventFacts,
            ],
            'payload' => $this->payload,
        ];
    }
}
