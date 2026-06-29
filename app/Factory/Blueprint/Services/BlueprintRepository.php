<?php

declare(strict_types=1);

namespace App\Factory\Blueprint\Services;

use App\Factory\Blueprint\DTO\SystemBlueprint;
use Illuminate\Support\Facades\File;
use RuntimeException;

class BlueprintRepository
{
    public function save(SystemBlueprint $blueprint): string
    {
        $dir = storage_path('app/factory/blueprints');

        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0775, true);
        }

        $path = $dir . '/' . $blueprint->slug . '.json';

        File::put($path, json_encode($blueprint->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    public function find(string $slug): SystemBlueprint
    {
        $path = storage_path('app/factory/blueprints/' . $slug . '.json');

        if (! File::exists($path)) {
            throw new RuntimeException("Blueprint não encontrado: {$path}");
        }

        $data = json_decode((string) File::get($path), true);

        return SystemBlueprint::fromArray($data);
    }
}
