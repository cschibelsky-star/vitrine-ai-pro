<?php

declare(strict_types=1);

namespace App\Factory\Core\Services;

use Illuminate\Support\Facades\File;

class FactoryUpdateService
{
    public function run(): array
    {
        $checks = [];

        $checks[] = $this->check('factory_directory', File::isDirectory(app_path('Factory')), 'Diretório app/Factory existe.');
        $checks[] = $this->check('factory_config', File::exists(config_path('factory.php')), 'Configuração config/factory.php existe.');
        $checks[] = $this->check('factory_plugins_config', File::exists(config_path('factory_plugins.php')), 'Configuração config/factory_plugins.php existe.');
        $checks[] = $this->check('factory_routes', File::exists(base_path('routes/factory.php')), 'Rotas da Factory existem.');
        $checks[] = $this->check('storage_builds', $this->ensureDir(storage_path('app/factory/builds')), 'Diretório de builds disponível.');
        $checks[] = $this->check('storage_blueprints', $this->ensureDir(storage_path('app/factory/blueprints')), 'Diretório de blueprints disponível.');
        $checks[] = $this->check('storage_learning', $this->ensureDir(storage_path('app/factory/learning')), 'Diretório de learning disponível.');
        $checks[] = $this->check('storage_dashboards', $this->ensureDir(storage_path('app/factory/dashboards')), 'Diretório de dashboards disponível.');
        $checks[] = $this->check('storage_architecture', $this->ensureDir(storage_path('app/factory/architecture')), 'Diretório de architecture disponível.');

        return [
            'status' => collect($checks)->contains(fn ($check) => $check['status'] === 'failed') ? 'failed' : 'passed',
            'checked_at' => now()->toISOString(),
            'checks' => $checks,
        ];
    }

    protected function ensureDir(string $path): bool
    {
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0775, true);
        }

        return File::isDirectory($path);
    }

    protected function check(string $name, bool $passed, string $message): array
    {
        return [
            'name' => $name,
            'status' => $passed ? 'passed' : 'failed',
            'message' => $message,
        ];
    }
}
