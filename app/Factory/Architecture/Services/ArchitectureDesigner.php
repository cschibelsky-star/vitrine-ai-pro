<?php

declare(strict_types=1);

namespace App\Factory\Architecture\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArchitectureDesigner
{
    public function __construct(
        protected DomainKnowledgeEngine $domains,
        protected ComponentMarketplace $components,
    ) {
    }

    public function design(string $prompt): array
    {
        $domainData = $this->domains->analyze($prompt);
        $components = $this->components->componentsFor($domainData['domain']);

        $architecture = [
            'name' => 'Arquitetura — ' . $domainData['label'],
            'slug' => Str::slug('arquitetura_' . $domainData['domain'], '_'),
            'prompt' => $prompt,
            'domain' => $domainData,
            'components' => $components,
            'modules' => array_map(fn ($module) => [
                'slug' => $module,
                'label' => Str::headline(str_replace('_', ' ', $module)),
                'recommended_components' => $this->recommendedComponentsForModule($module, $components),
            ], $domainData['modules']),
            'apis' => [
                'REST API por módulo',
                'Endpoints de listagem, criação, edição, exclusão',
                'Autenticação por token em fase futura',
            ],
            'dashboards' => $domainData['recommended_dashboards'],
            'roadmap' => [
                '1. Gerar blueprint do domínio',
                '2. Gerar módulos e relacionamentos',
                '3. Executar Smart QA',
                '4. Gerar dashboards e widgets',
                '5. Instalar em ambiente seguro',
                '6. Validar no painel Filament',
            ],
            'designed_at' => now()->toISOString(),
        ];

        $dir = storage_path('app/factory/architecture');
        File::ensureDirectoryExists($dir);

        $path = $dir . '/' . $architecture['slug'] . '.json';
        File::put($path, json_encode($architecture, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $architecture['path'] = $path;

        return $architecture;
    }

    protected function recommendedComponentsForModule(string $module, array $components): array
    {
        $keys = ['audit_log', 'timeline', 'dashboard'];

        if (str_contains($module, 'document')) {
            $keys[] = 'document_upload';
            $keys[] = 'checklist';
        }

        if (str_contains($module, 'contrato') || str_contains($module, 'licitacao')) {
            $keys[] = 'approval_workflow';
            $keys[] = 'expiration_alerts';
            $keys[] = 'pdf_export';
        }

        if (str_contains($module, 'evento')) {
            $keys[] = 'event_calendar';
        }

        if (str_contains($module, 'video')) {
            $keys[] = 'video_library';
        }

        return array_values(array_filter($components, fn ($component) => in_array($component['key'], $keys, true)));
    }
}
