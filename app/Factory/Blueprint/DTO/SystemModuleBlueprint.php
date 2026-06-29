<?php

declare(strict_types=1);

namespace App\Factory\Blueprint\DTO;

final readonly class SystemModuleBlueprint
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $label,
        public array $fields,
        public array $dashboardMetrics = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            label: (string) ($data['label'] ?? $data['name']),
            fields: array_map(fn ($field) => BlueprintField::fromArray($field), $data['fields'] ?? []),
            dashboardMetrics: $data['dashboard_metrics'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'label' => $this->label,
            'fields' => array_map(fn (BlueprintField $field) => $field->toArray(), $this->fields),
            'dashboard_metrics' => $this->dashboardMetrics,
        ];
    }
}
