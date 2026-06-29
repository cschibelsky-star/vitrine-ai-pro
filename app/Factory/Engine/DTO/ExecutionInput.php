<?php

declare(strict_types=1);

namespace App\Factory\Engine\DTO;

use App\Factory\Models\FactoryExecution;

final readonly class ExecutionInput
{
    public function __construct(
        public int $executionId,
        public ?int $projectId,
        public ?int $capabilityId,
        public ?int $blueprintId,
        public array $input,
        public array $context = [],
    ) {
    }

    public static function fromExecution(FactoryExecution $execution): self
    {
        return new self(
            executionId: (int) $execution->id,
            projectId: $execution->factory_project_id ? (int) $execution->factory_project_id : null,
            capabilityId: $execution->factory_capability_id ? (int) $execution->factory_capability_id : null,
            blueprintId: $execution->factory_blueprint_id ? (int) $execution->factory_blueprint_id : null,
            input: $execution->input ?? [],
            context: $execution->context ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'execution_id' => $this->executionId,
            'project_id' => $this->projectId,
            'capability_id' => $this->capabilityId,
            'blueprint_id' => $this->blueprintId,
            'input' => $this->input,
            'context' => $this->context,
        ];
    }
}
