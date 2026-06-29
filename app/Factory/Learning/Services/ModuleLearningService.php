<?php

declare(strict_types=1);

namespace App\Factory\Learning\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class ModuleLearningService
{
    public function learn(string $slug): array
    {
        $modulePath = storage_path('app/factory/builds/' . $slug);
        $manifestPath = $modulePath . '/module.json';

        if (! File::isDirectory($modulePath)) {
            throw new RuntimeException("Módulo não encontrado: {$modulePath}");
        }

        $manifest = File::exists($manifestPath)
            ? json_decode((string) File::get($manifestPath), true)
            : [];

        $knowledge = [
            'slug' => $slug,
            'module_path' => $modulePath,
            'manifest' => $manifest,
            'files' => $this->scanFiles($modulePath),
            'learned_at' => now()->toISOString(),
            'summary' => [
                'has_model' => count(glob($modulePath . '/app/Models/*.php') ?: []) > 0,
                'has_policy' => count(glob($modulePath . '/app/Policies/*.php') ?: []) > 0,
                'has_migration' => count(glob($modulePath . '/database/migrations/*.php') ?: []) > 0,
                'has_resource' => count(glob($modulePath . '/app/Filament/Resources/*Resource.php') ?: []) > 0,
                'has_pages' => count(glob($modulePath . '/app/Filament/Resources/*Resource/Pages/*.php') ?: []) > 0,
            ],
        ];

        $dir = storage_path('app/factory/learning/modules');
        File::ensureDirectoryExists($dir);

        $path = $dir . '/' . $slug . '.json';

        File::put($path, json_encode($knowledge, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $knowledge['knowledge_path'] = $path;

        return $knowledge;
    }

    protected function scanFiles(string $dir): array
    {
        $files = [];

        foreach (File::allFiles($dir) as $file) {
            $files[] = str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname());
        }

        sort($files);

        return $files;
    }
}
