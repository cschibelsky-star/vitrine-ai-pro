<?php

declare(strict_types=1);

namespace App\Factory\Builder\DTO;

final readonly class ModuleField
{
    public function __construct(
        public string $name,
        public string $type = 'string',
        public bool $nullable = true,
    ) {
    }

    public function migrationColumn(): string
    {
        $nullable = $this->nullable ? '->nullable()' : '';

        return match ($this->type) {
            'text' => "\$table->text('{$this->name}'){$nullable};",
            'boolean' => "\$table->boolean('{$this->name}')->default(false);",
            default => "\$table->string('{$this->name}'){$nullable};",
        };
    }

    public function fillableLine(): string
    {
        return "'{$this->name}',";
    }

    public function formComponent(): string
    {
        return match ($this->type) {
            'text' => "Textarea::make('{$this->name}')->label('{$this->label()}')->columnSpanFull(),",
            'boolean' => "Toggle::make('{$this->name}')->label('{$this->label()}'),",
            default => "TextInput::make('{$this->name}')->label('{$this->label()}')->maxLength(255),",
        };
    }

    public function tableColumn(): string
    {
        return "TextColumn::make('{$this->name}')->label('{$this->label()}')->searchable()->sortable(),";
    }

    public function label(): string
    {
        return str($this->name)->replace('_', ' ')->headline()->toString();
    }
}
