<?php

declare(strict_types=1);

namespace App\Factory\Builder\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;

class BuildPathResolver
{
    public static function forSystem(string $systemSlug, string $moduleSlug): string
    {
        return storage_path('app/factory/builds/' . $systemSlug . '/' . $moduleSlug);
    }

    public static function resolve(string $moduleSlug, ?string $systemSlug = null): string
    {
        if ($systemSlug !== null && $systemSlug !== '') {
            return self::forSystem($systemSlug, $moduleSlug);
        }

        $root = storage_path('app/factory/builds');
        $matches = [];
        $legacy = $root . DIRECTORY_SEPARATOR . $moduleSlug;

        if (File::isDirectory($legacy)) {
            $matches[] = $legacy;
        }

        foreach (File::directories($root) as $candidate) {
            $nested = $candidate . DIRECTORY_SEPARATOR . $moduleSlug;

            if (File::isDirectory($nested)) {
                $matches[] = $nested;
            }
        }

        $matches = array_values(array_unique($matches));

        if (count($matches) === 1) {
            return $matches[0];
        }

        if (count($matches) > 1) {
            throw new RuntimeException(
                'Ambiguous Factory build module [' . $moduleSlug . ']. Informe o system slug explicitamente.'
            );
        }

        return $legacy;
    }
}
