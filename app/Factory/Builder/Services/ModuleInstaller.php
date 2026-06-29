<?php

declare(strict_types=1);

namespace App\Factory\Builder\Services;

use RuntimeException;

class ModuleInstaller
{
    public function install(string $slug, bool $dryRun = false, bool $force = false): array
    {
        $sourceBase = storage_path('app/factory/builds/' . $slug);

        if (! is_dir($sourceBase)) {
            throw new RuntimeException("Módulo não encontrado em: {$sourceBase}");
        }

        $operations = $this->operations($sourceBase);

        foreach ($operations as $operation) {
            $source = $operation['source'];
            $target = $operation['target'];

            if (! file_exists($source)) {
                continue;
            }

            if (file_exists($target) && ! $force) {
                $operation['status'] = 'skipped_exists';
                $results[] = $operation;
                continue;
            }

            if ($dryRun) {
                $operation['status'] = file_exists($target) ? 'would_overwrite' : 'would_copy';
                $results[] = $operation;
                continue;
            }

            $targetDir = dirname($target);

            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }

            copy($source, $target);

            $operation['status'] = 'copied';
            $results[] = $operation;
        }

        return $results ?? [];
    }

    protected function operations(string $sourceBase): array
    {
        $files = $this->allFiles($sourceBase);

        $operations = [];

        foreach ($files as $source) {
            $relative = str_replace($sourceBase . DIRECTORY_SEPARATOR, '', $source);

            if ($relative === 'README_MODULO.md' || $relative === 'module.json') {
                $operations[] = [
                    'source' => $source,
                    'target' => base_path('storage/app/factory/installed/' . basename(dirname($sourceBase)) . '/' . $relative),
                ];
                continue;
            }

            $operations[] = [
                'source' => $source,
                'target' => base_path($relative),
            ];
        }

        return $operations;
    }

    protected function allFiles(string $dir): array
    {
        $result = [];

        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $result = array_merge($result, $this->allFiles($path));
            } else {
                $result[] = $path;
            }
        }

        return $result;
    }
}
