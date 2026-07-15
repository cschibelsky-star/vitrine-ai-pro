<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ArchitectureInventoryCommand extends Command
{
    protected $signature = 'architecture:inventory {--json : Exibe o inventário em JSON}';

    protected $description = 'Inventaria Models, Services e Resources Filament para a consolidação arquitetural.';

    public function handle(): int
    {
        $inventory = [
            'generated_at' => now()->toISOString(),
            'models' => $this->scanPhpFiles(app_path('Models')),
            'services' => $this->scanPhpFiles(app_path(), ['Service.php']),
            'filament_resources' => $this->scanPhpFiles(app_path('Filament/Resources')),
            'filament_pages' => $this->scanPhpFiles(app_path('Filament/Pages')),
            'console_commands' => $this->scanPhpFiles(app_path('Console/Commands')),
        ];

        $outputPath = storage_path('app/audits/architecture-inventory.json');
        File::ensureDirectoryExists(dirname($outputPath));
        File::put($outputPath, json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($this->option('json')) {
            $this->line((string) file_get_contents($outputPath));
        } else {
            $this->info('Inventário salvo em: '.$outputPath);
            foreach ($inventory as $key => $items) {
                if (is_array($items)) {
                    $this->line(sprintf('%s: %d', $key, count($items)));
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param array<int, string> $suffixes
     * @return array<int, array{path:string,class:string,layer:string}>
     */
    private function scanPhpFiles(string $directory, array $suffixes = []): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        return collect(File::allFiles($directory))
            ->filter(function ($file) use ($suffixes): bool {
                if ($file->getExtension() !== 'php') {
                    return false;
                }

                if ($suffixes === []) {
                    return true;
                }

                foreach ($suffixes as $suffix) {
                    if (str_ends_with($file->getFilename(), $suffix)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(function ($file): array {
                $relative = str_replace('\\', '/', $file->getRelativePathname());

                return [
                    'path' => $relative,
                    'class' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'layer' => $this->classifyLayer($relative),
                ];
            })
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function classifyLayer(string $path): string
    {
        $normalized = strtolower(str_replace('\\', '/', $path));
        $class = strtolower(pathinfo($normalized, PATHINFO_FILENAME));

        return match (true) {
            str_contains($normalized, 'shared/'),
            str_starts_with($class, 'ai'),
            str_contains($class, 'heygen') => 'Shared',
            str_contains($normalized, 'commercial') || str_contains($normalized, 'lead') => 'Commercial',
            str_contains($normalized, 'factory') || str_contains($normalized, 'build') || str_contains($normalized, 'provision') => 'Factory',
            str_contains($normalized, 'animal'), str_contains($normalized, 'vacina'), str_contains($normalized, 'prontuario') => 'Legacy',
            str_contains($normalized, 'core/'),
            str_contains($normalized, 'company'),
            str_contains($normalized, 'product'),
            str_contains($normalized, 'plan'),
            str_contains($normalized, 'license'),
            str_contains($normalized, 'contract'),
            str_contains($normalized, 'payment'),
            str_contains($normalized, 'user'),
            in_array($class, ['setting', 'subscription', 'supportticket', 'module'], true) => 'Core',
            default => 'Unclassified',
        };
    }
}
