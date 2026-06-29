<?php

declare(strict_types=1);

namespace App\Factory\Builder\DTO;

final readonly class ModuleBlueprint
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $modelName,
        public string $tableName,
        public string $listPageName,
        public array $fields,
    ) {
    }
}
