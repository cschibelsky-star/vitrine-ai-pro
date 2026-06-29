<?php

declare(strict_types=1);

namespace App\Factory\Blueprint\DTO;

final readonly class BlueprintField
{
    public function __construct(
        public string $name,
        public string $type = 'string',
        public bool $nullable = true,
        public ?string $relationship = null,
        public ?string $relatedModel = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            type: (string) ($data['type'] ?? 'string'),
            nullable: (bool) ($data['nullable'] ?? true),
            relationship: $data['relationship'] ?? null,
            relatedModel: $data['related_model'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'nullable' => $this->nullable,
            'relationship' => $this->relationship,
            'related_model' => $this->relatedModel,
        ];
    }
}
