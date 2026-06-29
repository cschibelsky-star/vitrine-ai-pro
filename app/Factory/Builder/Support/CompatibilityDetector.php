<?php

declare(strict_types=1);

namespace App\Factory\Builder\Support;

use Illuminate\Support\Facades\File;

class CompatibilityDetector
{
    public function detect(): array
    {
        return [
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'filament' => $this->filamentVersion(),
            'forms_form_available' => class_exists(\Filament\Forms\Form::class),
            'schemas_schema_available' => class_exists('Filament\\Schemas\\Schema'),
            'table_record_actions_available' => method_exists(\Filament\Tables\Table::class, 'recordActions'),
            'table_actions_namespace_available' => class_exists(\Filament\Tables\Actions\ViewAction::class),
            'recommended_template' => 'filament_tables_actions_forms_form',
        ];
    }

    protected function filamentVersion(): string
    {
        $path = base_path('composer.lock');

        if (! File::exists($path)) {
            return 'unknown';
        }

        $data = json_decode((string) File::get($path), true);

        foreach (($data['packages'] ?? []) as $package) {
            if (($package['name'] ?? '') === 'filament/filament') {
                return (string) ($package['version'] ?? 'unknown');
            }
        }

        return 'unknown';
    }
}
