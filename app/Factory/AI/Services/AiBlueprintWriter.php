<?php

declare(strict_types=1);

namespace App\Factory\AI\Services;

use Illuminate\Support\Facades\File;

class AiBlueprintWriter
{
    public function write(array $blueprint): string
    {
        $dir = storage_path('app/factory/blueprints');

        File::ensureDirectoryExists($dir);

        $path = $dir . '/' . $blueprint['slug'] . '.json';

        File::put($path, json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }
}
