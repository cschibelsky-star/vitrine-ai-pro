<?php

declare(strict_types=1);

namespace App\Factory\Builder\Services;

use App\Factory\Builder\Support\CompatibilityDetector;

class ModuleQaService
{
    public function __construct(protected CompatibilityDetector $compatibility)
    {
    }

    public function inspect(string $slug): array
    {
        $base = storage_path('app/factory/builds/' . $slug);
        $checks = [];
        $status = 'passed';

        if (! is_dir($base)) {
            return ['status' => 'failed', 'checks' => [['status' => 'failed', 'message' => 'Módulo não encontrado.']]];
        }

        foreach ($this->phpFiles($base) as $file) {
            exec('php -l ' . escapeshellarg($file), $out, $code);
            $checks[] = ['status' => $code === 0 ? 'passed' : 'failed', 'file' => $file, 'message' => $code === 0 ? 'PHP OK' : 'Erro de sintaxe'];
            if ($code !== 0) $status = 'failed';
        }

        foreach (glob($base . '/app/Filament/Resources/*Resource.php') ?: [] as $resource) {
            $content = file_get_contents($resource) ?: '';
            foreach (['Filament\\Schemas\\Schema', 'recordActions(', 'toolbarActions(', 'use Filament\\Actions\\'] as $bad) {
                $ok = ! str_contains($content, $bad);
                $checks[] = ['status' => $ok ? 'passed' : 'failed', 'file' => $resource, 'message' => $ok ? "Compatível: {$bad}" : "Incompatível: {$bad}"];
                if (! $ok) $status = 'failed';
            }
        }

        return ['status' => $status, 'compatibility' => $this->compatibility->detect(), 'checks' => $checks];
    }

    protected function phpFiles(string $dir): array
    {
        $files = [];
        foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) $files = array_merge($files, $this->phpFiles($path));
            elseif (str_ends_with($path, '.php')) $files[] = $path;
        }
        return $files;
    }
}
