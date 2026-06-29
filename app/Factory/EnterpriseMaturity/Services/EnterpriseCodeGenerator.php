<?php

declare(strict_types=1);

namespace App\Factory\EnterpriseMaturity\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EnterpriseCodeGenerator
{
    public function __construct(
        protected EnterpriseNameService $names,
    ) {
    }

    public function generate(string $blueprintSlug): array
    {
        $blueprintPath = storage_path('app/factory/blueprints/' . $blueprintSlug . '.json');

        if (! File::exists($blueprintPath)) {
            throw new \RuntimeException("Blueprint não encontrado: {$blueprintSlug}");
        }

        $blueprint = json_decode((string) File::get($blueprintPath), true);
        $base = storage_path('app/factory/enterprise-builds/' . $blueprintSlug);

        File::ensureDirectoryExists($base);

        $files = [];
        $apiRoutes = [];

        foreach (($blueprint['modules'] ?? []) as $module) {
            $slug = $module['slug'];
            $model = $this->names->modelName($slug);

            $files[] = $this->write($base . "/app/Repositories/{$model}Repository.php", $this->repositoryCode($slug));
            $files[] = $this->write($base . "/app/Services/{$model}Service.php", $this->serviceCode($slug));
            $files[] = $this->write($base . "/app/Http/Requests/Store{$model}Request.php", $this->requestCode($module, false));
            $files[] = $this->write($base . "/app/Http/Requests/Update{$model}Request.php", $this->requestCode($module, true));
            $files[] = $this->write($base . "/app/Http/Controllers/Api/{$model}ApiController.php", $this->apiControllerCode($slug));
            $files[] = $this->write($base . "/tests/Feature/{$model}ApiTest.php", $this->featureTestCode($slug));

            $apiRoutes[] = "Route::apiResource('" . $this->names->routeName($slug) . "', \\App\\Http\\Controllers\\Api\\{$model}ApiController::class);";
        }

        $files[] = $this->write($base . "/routes/api_factory_{$blueprintSlug}.php", $this->apiRoutesCode($apiRoutes));

        $report = [
            'blueprint' => $blueprintSlug,
            'status' => 'generated',
            'files_count' => count($files),
            'files' => $files,
            'generated_at' => now()->toISOString(),
        ];

        $reportPath = $base . '/ENTERPRISE_BUILD_REPORT.json';
        File::put($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $report['path'] = $reportPath;

        return $report;
    }

    protected function write(string $path, string $content): string
    {
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        return $path;
    }

    protected function repositoryCode(string $slug): string
    {
        $model = $this->names->modelName($slug);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\\{$model};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class {$model}Repository
{
    public function paginate(int \$perPage = 15): LengthAwarePaginator
    {
        return {$model}::query()->latest()->paginate(\$perPage);
    }

    public function all(): Collection
    {
        return {$model}::query()->latest()->get();
    }

    public function find(int|string \$id): ?{$model}
    {
        return {$model}::query()->find(\$id);
    }

    public function create(array \$data): {$model}
    {
        return {$model}::query()->create(\$data);
    }

    public function update({$model} \$record, array \$data): {$model}
    {
        \$record->update(\$data);

        return \$record->refresh();
    }

    public function delete({$model} \$record): bool
    {
        return (bool) \$record->delete();
    }
}

PHP;
    }

    protected function serviceCode(string $slug): string
    {
        $model = $this->names->modelName($slug);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\\{$model};
use App\Repositories\\{$model}Repository;
use Illuminate\Pagination\LengthAwarePaginator;

class {$model}Service
{
    public function __construct(
        protected {$model}Repository \$repository,
    ) {
    }

    public function paginate(int \$perPage = 15): LengthAwarePaginator
    {
        return \$this->repository->paginate(\$perPage);
    }

    public function create(array \$data): {$model}
    {
        return \$this->repository->create(\$data);
    }

    public function update({$model} \$record, array \$data): {$model}
    {
        return \$this->repository->update(\$record, \$data);
    }

    public function delete({$model} \$record): bool
    {
        return \$this->repository->delete(\$record);
    }
}

PHP;
    }

    protected function requestCode(array $module, bool $update): string
    {
        $slug = $module['slug'];
        $model = $this->names->modelName($slug);
        $class = ($update ? 'Update' : 'Store') . $model . 'Request';

        $rules = [];

        foreach (($module['fields'] ?? []) as $field) {
            $name = $field['name'];
            $nullable = ($field['nullable'] ?? true) || $update ? 'nullable' : 'required';
            $type = $field['type'] ?? 'string';

            if ($type === 'foreignId' || str_ends_with($name, '_id')) {
                $rules[] = "'{$name}' => ['{$nullable}', 'integer']";
            } elseif ($type === 'date') {
                $rules[] = "'{$name}' => ['{$nullable}', 'date']";
            } elseif ($type === 'decimal' || $type === 'integer') {
                $rules[] = "'{$name}' => ['{$nullable}', 'numeric']";
            } else {
                $rules[] = "'{$name}' => ['{$nullable}', 'string']";
            }
        }

        $rulesCode = implode(",\n            ", $rules);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {$class} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            {$rulesCode}
        ];
    }
}

PHP;
    }

    protected function apiControllerCode(string $slug): string
    {
        $model = $this->names->modelName($slug);
        $storeRequest = 'Store' . $model . 'Request';
        $updateRequest = 'Update' . $model . 'Request';
        $service = $model . 'Service';
        $var = $this->names->variableName($slug);

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\\{$storeRequest};
use App\Http\Requests\\{$updateRequest};
use App\Models\\{$model};
use App\Services\\{$service};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class {$model}ApiController extends Controller
{
    public function __construct(
        protected {$service} \$service,
    ) {
    }

    public function index(Request \$request): JsonResponse
    {
        return response()->json(\$this->service->paginate((int) \$request->integer('per_page', 15)));
    }

    public function store({$storeRequest} \$request): JsonResponse
    {
        \$record = \$this->service->create(\$request->validated());

        return response()->json(\$record, 201);
    }

    public function show({$model} \${$var}): JsonResponse
    {
        return response()->json(\${$var});
    }

    public function update({$updateRequest} \$request, {$model} \${$var}): JsonResponse
    {
        return response()->json(\$this->service->update(\${$var}, \$request->validated()));
    }

    public function destroy({$model} \${$var}): JsonResponse
    {
        \$this->service->delete(\${$var});

        return response()->json(['deleted' => true]);
    }
}

PHP;
    }

    protected function featureTestCode(string $slug): string
    {
        $model = $this->names->modelName($slug);
        $route = $this->names->routeName($slug);

        return <<<PHP
<?php

namespace Tests\Feature;

use Tests\TestCase;

class {$model}ApiTest extends TestCase
{
    public function test_api_index_endpoint_exists(): void
    {
        \$response = \$this->getJson('/api/{$route}');

        \$response->assertStatus(200);
    }
}

PHP;
    }

    protected function apiRoutesCode(array $routes): string
    {
        $routesCode = implode("\n", $routes);

        return <<<PHP
<?php

use Illuminate\Support\Facades\Route;

{$routesCode}

PHP;
    }
}
