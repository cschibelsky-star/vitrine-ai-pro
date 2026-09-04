<?php

declare(strict_types=1);

namespace App\Factory\Finalization\Services;

use App\Factory\AI\Services\AdvancedRequirementAnalyzer;
use Illuminate\Support\Facades\File;

class AiArchitectFinalService
{
    public function __construct(
        protected AdvancedRequirementAnalyzer $analyzer,
    ) {
    }

    public function architect(string $request): array
    {
        $analyzed = $this->analyzer->analyze($request);
        $domain = (string) ($analyzed['architecture']['domain'] ?? 'generico');

        $blueprint = [
            'name' => $analyzed['name'],
            'slug' => $analyzed['slug'],
            'description' => $request,
            'domain' => $domain,
            'modules' => $analyzed['modules'] ?? [],
            'architecture' => $analyzed['architecture'] ?? [],
            'generated_by' => 'FACTORY_INTELLIGENCE_CORE',
            'generated_at' => now()->toISOString(),
        ];

        File::ensureDirectoryExists(storage_path('app/factory/blueprints'));
        File::ensureDirectoryExists(storage_path('app/factory/finalization/architectures'));

        $blueprintPath = storage_path('app/factory/blueprints/' . $blueprint['slug'] . '.json');
        $architecturePath = storage_path('app/factory/finalization/architectures/' . date('Ymd_His') . '_' . $blueprint['slug'] . '.json');

        File::put($blueprintPath, json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        File::put($architecturePath, json_encode($blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return [
            'request' => $request,
            'domain' => $domain,
            'blueprint' => $blueprint,
            'blueprint_path' => $blueprintPath,
            'architecture_path' => $architecturePath,
        ];
    }
}
