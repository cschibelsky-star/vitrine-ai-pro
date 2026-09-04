<?php

use App\Factory\Intake\Services\FactoryViaIntakeService;
use App\Factory\Models\FactoryProject;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

Artisan::command('about-master', function () {
    $this->info('Vitrine AI Pro Master Start MVP');
});

Artisan::command('factory:intake-probe {request}', function (FactoryViaIntakeService $intakeService) {
    $path = storage_path('app/factory/intake-probe.json');
    @mkdir(dirname($path), 0775, true);

    try {
        $result = $intakeService->prepare((string) $this->argument('request'));
        file_put_contents($path, json_encode([
            'ok' => true,
            'generated_at' => now()->toISOString(),
            'result' => $result,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->info('FACTORY_INTAKE_PROBE_OK');
        return 0;
    } catch (Throwable $e) {
        file_put_contents($path, json_encode([
            'ok' => false,
            'generated_at' => now()->toISOString(),
            'error' => $e->getMessage(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->error('FACTORY_INTAKE_PROBE_FAILED');
        return 1;
    }
});

Artisan::command('factory:discovery-validation-probe {projectId}', function (FactoryViaIntakeService $intakeService) {
    $projectId = (int) $this->argument('projectId');
    $path = storage_path('app/factory/discovery-validation-probe.json');
    @mkdir(dirname($path), 0775, true);

    $evidence = [
        'factory' => ['checked' => true, 'matches' => []],
        'github' => ['checked' => true, 'matches' => []],
        'n8n' => ['checked' => true, 'matches' => []],
    ];

    try {
        $result = $intakeService->validateDiscovery($projectId, $evidence);
        file_put_contents($path, json_encode(['ok' => true, 'project_id' => $projectId, 'result' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->info('FACTORY_DISCOVERY_VALIDATION_PROBE_OK');
        return 0;
    } catch (Throwable $e) {
        file_put_contents($path, json_encode(['ok' => false, 'project_id' => $projectId, 'error' => $e->getMessage()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->error('FACTORY_DISCOVERY_VALIDATION_PROBE_FAILED');
        return 1;
    }
});

Artisan::command('factory:execute-approved-probe {projectId}', function (FactoryViaIntakeService $intakeService) {
    $path = storage_path('app/factory/execute-approved-probe.json');
    @mkdir(dirname($path), 0775, true);

    try {
        $result = $intakeService->executeApproved((int) $this->argument('projectId'));
        file_put_contents($path, json_encode([
            'ok' => false,
            'unexpected_execution' => true,
            'generated_at' => now()->toISOString(),
            'project_id' => (int) $this->argument('projectId'),
            'result' => $result,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->error('FACTORY_EXECUTE_APPROVED_PROBE_UNEXPECTED_EXECUTION');
        return 2;
    } catch (Throwable $e) {
        $blocked = str_starts_with($e->getMessage(), 'Discovery Gate bloqueou a construção:');
        file_put_contents($path, json_encode([
            'ok' => $blocked,
            'blocked_before_build' => $blocked,
            'generated_at' => now()->toISOString(),
            'project_id' => (int) $this->argument('projectId'),
            'message' => $e->getMessage(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($blocked) {
            $this->info('FACTORY_EXECUTE_APPROVED_BLOCKED_OK');
            return 0;
        }
        $this->error('FACTORY_EXECUTE_APPROVED_PROBE_FAILED');
        return 1;
    }
});

Artisan::command('factory:discovery-gate-probe {projectId}', function (FactoryViaIntakeService $intakeService) {
    $path = storage_path('app/factory/discovery-gate-probe.json');
    @mkdir(dirname($path), 0775, true);

    try {
        $gate = $intakeService->discoveryDecisionForProject((int) $this->argument('projectId'));
        file_put_contents($path, json_encode([
            'ok' => true,
            'generated_at' => now()->toISOString(),
            'project_id' => (int) $this->argument('projectId'),
            'gate' => $gate,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->info('FACTORY_DISCOVERY_GATE_PROBE_OK');
        return 0;
    } catch (Throwable $e) {
        file_put_contents($path, json_encode([
            'ok' => false,
            'generated_at' => now()->toISOString(),
            'project_id' => (int) $this->argument('projectId'),
            'error' => $e->getMessage(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->error('FACTORY_DISCOVERY_GATE_PROBE_FAILED');
        return 1;
    }
});
