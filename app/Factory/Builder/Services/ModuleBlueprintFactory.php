<?php

declare(strict_types=1);

namespace App\Factory\Builder\Services;

use App\Factory\Builder\DTO\ModuleBlueprint;
use App\Factory\Builder\DTO\ModuleField;
use App\Factory\Builder\Support\PortugueseNameHelper;

class ModuleBlueprintFactory
{
    public function __construct(
        protected PortugueseNameHelper $names,
    ) {
    }

    public function make(string $name): ModuleBlueprint
    {
        return new ModuleBlueprint(
            name: $this->names->title($name),
            slug: $this->names->slug($name),
            modelName: $this->names->modelName($name),
            tableName: $this->names->tableName($name),
            listPageName: $this->names->listPageName($name),
            fields: [
                new ModuleField('nome', 'string', false),
                new ModuleField('documento'),
                new ModuleField('email'),
                new ModuleField('telefone'),
                new ModuleField('cidade'),
                new ModuleField('status', 'string', false),
            ],
        );
    }
}
