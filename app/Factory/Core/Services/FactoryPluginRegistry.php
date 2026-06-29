<?php

declare(strict_types=1);

namespace App\Factory\Core\Services;

class FactoryPluginRegistry
{
    public function all(): array
    {
        return config('factory_plugins', []);
    }

    public function active(): array
    {
        return array_filter(
            $this->all(),
            fn (array $plugin): bool => ($plugin['status'] ?? null) === 'active'
        );
    }

    public function summary(): array
    {
        $plugins = $this->all();

        return [
            'total' => count($plugins),
            'active' => count($this->active()),
            'plugins' => $plugins,
        ];
    }
}
