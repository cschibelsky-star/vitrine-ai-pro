<?php

declare(strict_types=1);

namespace App\Factory\Learning\Services;

use Illuminate\Support\Facades\File;

class SmartQaService
{
    public function inspect(string $slug): array
    {
        $modulePath = storage_path('app/factory/builds/' . $slug);
        $checks = [];
        $status = 'passed';

        $this->add($checks, 'module_exists', File::isDirectory($modulePath), 'Módulo existe em storage/app/factory/builds.');

        if (! File::isDirectory($modulePath)) {
            return [
                'module' => $slug,
                'status' => 'failed',
                'checks' => $checks,
            ];
        }

        $this->add($checks, 'manifest_exists', File::exists($modulePath . '/module.json'), 'Arquivo module.json encontrado.');
        $this->add($checks, 'has_model', count(glob($modulePath . '/app/Models/*.php') ?: []) > 0, 'Model gerado.');
        $this->add($checks, 'has_policy', count(glob($modulePath . '/app/Policies/*.php') ?: []) > 0, 'Policy gerada.');
        $this->add($checks, 'has_migration', count(glob($modulePath . '/database/migrations/*.php') ?: []) > 0, 'Migration gerada.');
        $this->add($checks, 'has_resource', count(glob($modulePath . '/app/Filament/Resources/*Resource.php') ?: []) > 0, 'Filament Resource gerado.');
        $this->add($checks, 'has_pages', count(glob($modulePath . '/app/Filament/Resources/*Resource/Pages/*.php') ?: []) >= 4, 'Pages Filament geradas.');

        foreach (File::allFiles($modulePath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            exec('php -l ' . escapeshellarg($file->getPathname()), $output, $code);
            $this->add($checks, 'php_lint', $code === 0, 'Sintaxe PHP OK: ' . str_replace($modulePath . '/', '', $file->getPathname()));
        }

        foreach (glob($modulePath . '/app/Filament/Resources/*Resource.php') ?: [] as $resource) {
            $content = (string) File::get($resource);

            $this->add($checks, 'filament_no_schema_api', ! str_contains($content, 'Filament\\Schemas\\Schema'), 'Não usa API Filament Schemas incompatível.');
            $this->add($checks, 'filament_no_record_actions', ! str_contains($content, 'recordActions('), 'Não usa recordActions incompatível.');
            $this->add($checks, 'filament_no_toolbar_actions', ! str_contains($content, 'toolbarActions('), 'Não usa toolbarActions incompatível.');
            $this->add($checks, 'filament_tables_actions', ! str_contains($content, 'use Filament\\Actions\\'), 'Usa actions compatíveis com tabela.');
        }

        foreach ($checks as $check) {
            if ($check['status'] === 'failed') {
                $status = 'failed';
                break;
            }
        }

        return [
            'module' => $slug,
            'status' => $status,
            'checks' => $checks,
            'checked_at' => now()->toISOString(),
        ];
    }

    protected function add(array &$checks, string $name, bool $passed, string $message): void
    {
        $checks[] = [
            'name' => $name,
            'status' => $passed ? 'passed' : 'failed',
            'message' => $message,
        ];
    }
}
