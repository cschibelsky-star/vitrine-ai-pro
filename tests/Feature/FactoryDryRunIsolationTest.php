<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Factory\Console\Commands\FactoryProduceCommand;
use App\Factory\Console\Commands\FactoryProduceEnterpriseCommand;
use App\Factory\FinalMaster\Services\FactoryFinalMasterService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FactoryDryRunIsolationTest extends TestCase
{
    public function test_factory_production_commands_have_unique_names(): void
    {
        $commands = Artisan::all();

        $this->assertInstanceOf(
            FactoryProduceCommand::class,
            $commands['factory:produce']
        );

        $this->assertInstanceOf(
            FactoryProduceEnterpriseCommand::class,
            $commands['factory:produce-enterprise']
        );
    }

    public function test_dry_run_uses_isolated_storage_and_restores_application_storage(): void
    {
        $applicationStorage = storage_path();
        $temporaryStorage = sys_get_temp_dir()
            . '/factory-dry-run-test-'
            . bin2hex(random_bytes(6));

        File::ensureDirectoryExists($temporaryStorage);
        app()->useStoragePath($temporaryStorage);

        $sharedArtifact = storage_path('app/factory/builds/existing/module.json');
        File::ensureDirectoryExists(dirname($sharedArtifact));
        File::put($sharedArtifact, '{"preserved":true}');

        $service = new class extends FactoryFinalMasterService {
            public array $commandStoragePaths = [];

            protected function call(string $command, array $args = []): array
            {
                $this->commandStoragePaths[] = storage_path();

                return [
                    'command' => $command,
                    'arguments' => $args,
                    'exit_code' => 0,
                    'status' => 'passed',
                    'output' => '',
                ];
            }

            protected function detectLatestBlueprint(): ?string
            {
                return 'gov360';
            }
        };

        try {
            $report = $service->buildAndInstall(
                'Criar instância de homologação do produto Gov360',
                true,
                true,
                true,
            );

            $this->assertSame('dry_run', $report['mode']);
            $this->assertTrue($report['shared_storage_untouched']);
            $this->assertFalse($report['migrate']);
            $this->assertFileExists($report['path']);
            $this->assertSame('{"preserved":true}', File::get($sharedArtifact));
            $this->assertSame($temporaryStorage, storage_path());

            foreach ($service->commandStoragePaths as $commandStoragePath) {
                $this->assertSame(
                    $report['sandbox_storage_path'],
                    $commandStoragePath
                );
            }
        } finally {
            app()->useStoragePath($applicationStorage);
            File::deleteDirectory($temporaryStorage);
        }
    }
}
