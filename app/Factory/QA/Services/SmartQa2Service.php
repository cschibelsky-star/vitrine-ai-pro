<?php

declare(strict_types=1);

namespace App\Factory\QA\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class SmartQa2Service
{
    public function inspect(): array
    {
        $checks = [];

        $this->add($checks, 'release_config', File::exists(config_path('factory_release.php')), 'Configuração de release existe.');
        $this->add($checks, 'products_config', File::exists(config_path('factory_products.php')), 'Catálogo de produtos existe.');
        $this->add($checks, 'factory_bootstrap', File::exists(base_path('factory_release_bootstrap.py')), 'Bootstrap de release existe.');
        $this->add($checks, 'factory_core', File::isDirectory(app_path('Factory')), 'Factory está instalada.');
        $this->add($checks, 'composer_manifest', File::exists(base_path('composer.json')) && File::exists(base_path('composer.lock')), 'Composer manifest e lock disponíveis.');
        $this->add($checks, 'autoload', File::exists(base_path('vendor/autoload.php')), 'Autoload do Composer disponível.');

        $this->add($checks, 'storage_releases', $this->ensureWritable(storage_path('app/factory/releases')), 'Storage de releases disponível e gravável.');
        $this->add($checks, 'storage_products', $this->ensureWritable(storage_path('app/factory/products')), 'Storage de produtos disponível e gravável.');
        $this->add($checks, 'storage_docs', $this->ensureWritable(storage_path('app/factory/docs')), 'Storage de documentação disponível e gravável.');
        $this->add($checks, 'storage_history', $this->ensureWritable(storage_path('app/factory/history')), 'Storage de histórico disponível e gravável.');

        $this->add($checks, 'database_read', $this->databaseReadable(), 'Banco da Factory responde a consulta somente leitura.');

        foreach ($this->criticalPhpFiles() as $key => $path) {
            $this->add($checks, 'php_lint_'.$key, $this->phpLint($path), 'PHP lint: '.$path);
        }

        foreach ($this->criticalComponents() as $key => $path) {
            $this->add($checks, 'component_'.$key, File::exists($path), 'Componente crítico disponível: '.$path);
        }

        return [
            'status' => collect($checks)->contains(fn ($check) => $check['status'] === 'failed') ? 'failed' : 'passed',
            'checks' => $checks,
            'checked_at' => now()->toISOString(),
        ];
    }

    protected function ensureWritable(string $path): bool
    {
        File::ensureDirectoryExists($path);

        return File::isDirectory($path) && is_writable($path);
    }

    protected function databaseReadable(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function phpLint(string $path): bool
    {
        if (! File::exists($path)) {
            return false;
        }

        $output = [];
        $exitCode = 1;
        exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($path).' 2>&1', $output, $exitCode);

        return $exitCode === 0;
    }

    /** @return array<string,string> */
    protected function criticalPhpFiles(): array
    {
        return [
            'final_master' => app_path('Factory/FinalMaster/Services/FactoryFinalMasterService.php'),
            'real_build' => app_path('Factory/RealBuilder/Services/RealBuildInstaller.php'),
            'enterprise_build' => app_path('Factory/EnterpriseMaturity/Services/EnterpriseBuildInstaller.php'),
            'smart_qa2' => app_path('Factory/QA/Services/SmartQa2Service.php'),
            'studio_page' => app_path('Filament/Pages/FactoryStudioEnterprise.php'),
            'site_factory_routes' => base_path('routes/site_factory_api.php'),
            'generated_routes' => base_path('routes/factory_generated.php'),
        ];
    }

    /** @return array<string,string> */
    protected function criticalComponents(): array
    {
        return [
            'url_provisioner' => app_path('Factory/Production/Services/ProjectUrlProvisioner.php'),
            'final_master' => app_path('Factory/FinalMaster/Services/FactoryFinalMasterService.php'),
            'real_installer' => app_path('Factory/RealBuilder/Services/RealBuildInstaller.php'),
            'enterprise_installer' => app_path('Factory/EnterpriseMaturity/Services/EnterpriseBuildInstaller.php'),
        ];
    }

    protected function add(array &$checks, string $key, bool $passed, string $message): void
    {
        $checks[] = [
            'key' => $key,
            'status' => $passed ? 'passed' : 'failed',
            'message' => $message,
        ];
    }
}
