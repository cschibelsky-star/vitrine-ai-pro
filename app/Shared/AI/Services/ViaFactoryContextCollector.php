<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use App\Factory\Enums\FactoryStage;
use App\Http\Requests\SiteFactoryIntakeRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

class ViaFactoryContextCollector
{
    public function __construct(private readonly FactoryKernelMcpClient $kernel)
    {
    }

    public function collect(): array
    {
        $base = storage_path('app/factory/commercial-intake');
        $directories = File::isDirectory($base)
            ? collect(File::directories($base))->sortDesc()->values()
            : collect();

        $latest = $directories->first();
        $latestIntake = $this->readJson($latest ? $latest . '/commercial_intake.json' : null);
        $latestReport = $this->readJson($latest ? $latest . '/commercial_factory_report.json' : null);

        $factoryFiles = $this->phpInventory(app_path('Factory'), 'app/Factory');
        $commercialFactoryFiles = $this->phpInventory(app_path('Commercial/Factory'), 'app/Commercial/Factory');
        $factoryCommands = collect(array_keys(Artisan::all()))
            ->filter(fn (string $name): bool => str_contains(strtolower($name), 'factory'))
            ->sort()
            ->values()
            ->all();
        $factoryRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(function ($route): bool {
                $uri = strtolower((string) $route->uri());
                $name = strtolower((string) ($route->getName() ?? ''));

                return str_contains($uri, 'factory') || str_contains($name, 'factory');
            })
            ->map(fn ($route): array => [
                'methods' => array_values(array_diff($route->methods(), ['HEAD'])),
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => array_values($route->gatherMiddleware()),
            ])
            ->values()
            ->all();

        $currentSchema = [
            'intake_fields' => ['client', 'product', 'plan', 'project_slug', 'prompt', 'dry_run', 'stage'],
            'report_fields' => ['status', 'stage', 'project_slug', 'commercial_status', 'dry_run', 'exit_code', 'path', 'created_at'],
        ];

        $tokenConfigured = filled(config('site_factory.token'));
        $kernelContext = $this->kernelContext();

        return [
            'collector' => 'factory_local_readonly_v2',
            'collected_at' => now()->toISOString(),
            'read_only' => true,
            'sources' => [
                'commercial_intake_storage' => [
                    'path' => 'storage/app/factory/commercial-intake',
                    'exists' => File::isDirectory($base),
                    'entries' => $directories->count(),
                    'latest_entry' => $latest ? basename($latest) : null,
                ],
                'factory_code_inventory' => [
                    'app_factory' => $factoryFiles,
                    'app_commercial_factory' => $commercialFactoryFiles,
                ],
                'factory_commands' => [
                    'count' => count($factoryCommands),
                    'registered' => $factoryCommands,
                    'executed' => false,
                ],
                'factory_routes' => [
                    'count' => count($factoryRoutes),
                    'registered' => $factoryRoutes,
                ],
                'factory_stages' => array_map(
                    fn (FactoryStage $stage): string => $stage->value,
                    FactoryStage::cases()
                ),
                'intake_security' => [
                    'request_class' => SiteFactoryIntakeRequest::class,
                    'authorization_method_present' => method_exists(SiteFactoryIntakeRequest::class, 'authorize'),
                    'token_configured' => $tokenConfigured,
                    'token_header' => 'X-Site-Factory-Token',
                    'payload_validation_present' => method_exists(SiteFactoryIntakeRequest::class, 'rules'),
                    'specific_rate_limit_observed' => $this->hasSpecificThrottle($factoryRoutes, 'api/site/factory/intake'),
                    'endpoint_state' => $tokenConfigured ? 'authenticated_ready' : 'fail_closed_token_missing',
                    'security_interpretation' => $tokenConfigured
                        ? 'Endpoint exige token configurado e validação de payload.'
                        : 'Endpoint permanece bloqueado por padrão porque o token obrigatório não está configurado.',
                ],
                'current_storage_schema' => $currentSchema,
                'factory_kernel' => $kernelContext,
            ],
            'latest_intake' => $this->sanitizeIntake($latestIntake),
            'latest_report' => $this->sanitizeReport($latestReport),
            'field_presence' => [
                'latest_intake' => $this->fieldPresence($latestIntake, $currentSchema['intake_fields']),
                'latest_report' => $this->fieldPresence($latestReport, $currentSchema['report_fields']),
            ],
            'schema_assessment' => [
                'latest_intake_matches_current_schema' => $this->containsAllKeys($latestIntake, $currentSchema['intake_fields']),
                'latest_report_matches_current_schema' => $this->containsAllKeys($latestReport, $currentSchema['report_fields']),
                'historical_record_may_predate_current_schema' => ! $this->containsAllKeys($latestIntake, $currentSchema['intake_fields'])
                    || ! $this->containsAllKeys($latestReport, $currentSchema['report_fields']),
            ],
            'safety' => [
                'files_written' => 0,
                'commands_executed' => 0,
                'deploys' => 0,
                'destructive_actions' => 0,
            ],
        ];
    }

    public function compactContext(): string
    {
        $full = $this->collect();

        $compact = [
            'collector' => $full['collector'] ?? null,
            'read_only' => true,
            'intakes' => data_get($full, 'sources.commercial_intake_storage.entries', 0),
            'latest_entry' => data_get($full, 'sources.commercial_intake_storage.latest_entry'),
            'php_inventory' => [
                'app_factory_count' => data_get($full, 'sources.factory_code_inventory.app_factory.php_files', 0),
                'app_factory_sample' => array_slice((array) data_get($full, 'sources.factory_code_inventory.app_factory.files', []), 0, 20),
                'commercial_factory_count' => data_get($full, 'sources.factory_code_inventory.app_commercial_factory.php_files', 0),
                'commercial_factory_sample' => array_slice((array) data_get($full, 'sources.factory_code_inventory.app_commercial_factory.files', []), 0, 12),
            ],
            'commands' => [
                'count' => data_get($full, 'sources.factory_commands.count', 0),
                'sample' => array_slice((array) data_get($full, 'sources.factory_commands.registered', []), 0, 20),
                'executed' => false,
            ],
            'routes' => data_get($full, 'sources.factory_routes.registered', []),
            'security' => data_get($full, 'sources.intake_security', []),
            'factory_kernel' => data_get($full, 'sources.factory_kernel', []),
            'stages' => data_get($full, 'sources.factory_stages', []),
            'latest_intake' => $full['latest_intake'] ?? [],
            'latest_report' => $full['latest_report'] ?? [],
            'field_presence' => $full['field_presence'] ?? [],
            'schema_assessment' => $full['schema_assessment'] ?? [],
            'safety' => $full['safety'] ?? [],
        ];

        return json_encode($compact, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function kernelContext(): array
    {
        if (! (bool) config('factory_kernel.enabled', true)) {
            return [
                'available' => false,
                'state' => 'disabled',
                'policy' => 'discovery_required',
                'evidence_gap' => 'Factory Kernel MCP está desabilitado no Core.',
            ];
        }

        try {
            $manifest = $this->kernel->manifest();

            return [
                'available' => true,
                'state' => 'ready',
                'kernel_version' => $manifest['kernel_version'] ?? null,
                'generated_at' => $manifest['generated_at'] ?? null,
                'policy' => $manifest['policy'] ?? null,
                'existing' => array_values((array) ($manifest['existing'] ?? [])),
                'missing' => array_values((array) ($manifest['missing'] ?? [])),
                'duplicate_signals' => array_values((array) ($manifest['duplicate_signals'] ?? [])),
                'artisan_command_count' => $manifest['artisan_command_count'] ?? null,
                'container_count' => $manifest['container_count'] ?? null,
                'read_only' => true,
            ];
        } catch (\Throwable $exception) {
            return [
                'available' => false,
                'state' => 'evidence_gap',
                'policy' => 'discovery_required',
                'evidence_gap' => $exception->getMessage(),
                'read_only' => true,
            ];
        }
    }

    private function hasSpecificThrottle(array $routes, string $uri): bool
    {
        foreach ($routes as $route) {
            if (($route['uri'] ?? null) !== $uri) {
                continue;
            }

            foreach ((array) ($route['middleware'] ?? []) as $middleware) {
                if (str_starts_with((string) $middleware, 'throttle:')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function containsAllKeys(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }
        }

        return true;
    }

    private function fieldPresence(array $data, array $keys): array
    {
        $presence = [];
        foreach ($keys as $key) {
            $presence[$key] = [
                'present' => array_key_exists($key, $data),
                'is_null' => array_key_exists($key, $data) ? $data[$key] === null : null,
            ];
        }
        return $presence;
    }

    private function phpInventory(string $absolutePath, string $displayPath): array
    {
        if (! File::isDirectory($absolutePath)) {
            return ['path' => $displayPath, 'exists' => false, 'php_files' => 0, 'files' => []];
        }

        $files = collect(File::allFiles($absolutePath))
            ->filter(fn ($file): bool => strtolower($file->getExtension()) === 'php')
            ->map(function ($file) use ($absolutePath, $displayPath): string {
                $relative = ltrim(str_replace($absolutePath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
                return $displayPath . '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            })
            ->sort()->values()->all();

        return [
            'path' => $displayPath,
            'exists' => true,
            'php_files' => count($files),
            'files' => array_slice($files, 0, 80),
            'truncated' => count($files) > 80,
        ];
    }

    private function readJson(?string $path): array
    {
        if (! $path || ! File::isFile($path)) {
            return [];
        }
        $decoded = json_decode((string) File::get($path), true);
        return is_array($decoded) ? $decoded : [];
    }

    private function sanitizeIntake(array $data): array
    {
        if ($data === []) {
            return [];
        }
        return [
            'product' => $data['product'] ?? null,
            'plan' => $data['plan'] ?? null,
            'project_slug' => $data['project_slug'] ?? null,
            'dry_run' => $data['dry_run'] ?? null,
            'stage' => $data['stage'] ?? null,
        ];
    }

    private function sanitizeReport(array $data): array
    {
        if ($data === []) {
            return [];
        }
        return [
            'status' => $data['status'] ?? null,
            'stage' => $data['stage'] ?? null,
            'project_slug' => $data['project_slug'] ?? null,
            'commercial_status' => $data['commercial_status'] ?? null,
            'dry_run' => $data['dry_run'] ?? null,
            'exit_code' => $data['exit_code'] ?? null,
            'created_at' => $data['created_at'] ?? null,
        ];
    }
}
