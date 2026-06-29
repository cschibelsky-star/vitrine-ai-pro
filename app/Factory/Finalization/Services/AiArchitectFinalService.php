<?php

declare(strict_types=1);

namespace App\Factory\Finalization\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AiArchitectFinalService
{
    public function architect(string $request): array
    {
        $domain = $this->detectDomain($request);
        $template = config('factory_4_domains.' . $domain);

        if (! $template) {
            $template = $this->genericTemplate($request);
        }

        $blueprint = [
            'name' => $template['name'],
            'slug' => $template['slug'],
            'description' => $request,
            'domain' => $domain,
            'modules' => $template['modules'],
            'generated_by' => 'FACTORY_4_0_FINALIZATION_PACK',
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

    protected function detectDomain(string $request): string
    {
        $text = Str::of($request)->lower()->ascii()->toString();

        foreach (config('factory_4_domains', []) as $key => $domain) {
            foreach (($domain['keywords'] ?? []) as $keyword) {
                if (str_contains($text, Str::of($keyword)->lower()->ascii()->toString())) {
                    return $key;
                }
            }
        }

        if (str_contains($text, 'governo') || str_contains($text, 'licitacao') || str_contains($text, 'vender')) {
            return 'gov360_known';
        }

        return 'generic';
    }

    protected function genericTemplate(string $request): array
    {
        $slug = Str::slug(Str::limit($request, 45, ''), '_');

        if (! $slug) {
            $slug = 'sistema_generico';
        }

        return [
            'name' => Str::headline(str_replace('_', ' ', $slug)),
            'slug' => $slug,
            'modules' => [
                [
                    'name' => 'Registros',
                    'slug' => 'registros',
                    'label' => 'Registros',
                    'fields' => [
                        ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                        ['name' => 'descricao', 'type' => 'text', 'nullable' => true],
                        ['name' => 'status', 'type' => 'string', 'nullable' => false],
                    ],
                    'dashboard_metrics' => ['total', 'ativos'],
                ],
                [
                    'name' => 'Categorias',
                    'slug' => 'categorias',
                    'label' => 'Categorias',
                    'fields' => [
                        ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                        ['name' => 'status', 'type' => 'string', 'nullable' => false],
                    ],
                    'dashboard_metrics' => ['total'],
                ],
                [
                    'name' => 'Documentos',
                    'slug' => 'documentos',
                    'label' => 'Documentos',
                    'fields' => [
                        ['name' => 'registro_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Registro'],
                        ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                        ['name' => 'arquivo', 'type' => 'string', 'nullable' => true],
                        ['name' => 'status', 'type' => 'string', 'nullable' => false],
                    ],
                    'dashboard_metrics' => ['total'],
                ],
            ],
        ];
    }
}
