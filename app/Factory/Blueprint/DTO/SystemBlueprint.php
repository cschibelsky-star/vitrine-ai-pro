<?php

declare(strict_types=1);

namespace App\Factory\Blueprint\DTO;

final readonly class SystemBlueprint
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $description,
        public array $modules,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) $data['name'],
            slug: (string) $data['slug'],
            description: (string) ($data['description'] ?? ''),
            modules: array_map(fn ($module) => SystemModuleBlueprint::fromArray($module), $data['modules'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'modules' => array_map(fn (SystemModuleBlueprint $module) => $module->toArray(), $this->modules),
        ];
    }
}
