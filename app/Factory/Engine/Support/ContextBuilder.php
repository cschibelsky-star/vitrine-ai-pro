<?php

declare(strict_types=1);

namespace App\Factory\Engine\Support;

use App\Factory\Models\FactoryExecution;

class ContextBuilder
{
    public function build(FactoryExecution $execution): array
    {
        return [
            'execution' => [
                'id' => $execution->id,
                'name' => $execution->name,
                'status' => $execution->status,
                'attempt' => $execution->attempt,
            ],
            'project' => $execution->project?->only(['id', 'name', 'slug', 'status']),
            'capability' => $execution->capability?->only(['id', 'name', 'slug', 'type', 'status']),
            'blueprint' => $execution->blueprint?->only(['id', 'name', 'slug', 'status', 'schema', 'instructions']),
            'input' => $execution->input ?? [],
            'context' => $execution->context ?? [],
        ];
    }
}
