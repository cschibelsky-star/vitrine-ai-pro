<?php

declare(strict_types=1);

namespace App\Factory\AI\Services;

use Illuminate\Support\Str;

class RequirementAnalyzer
{
    public function analyze(string $prompt): array
    {
        $text = Str::of($prompt)->lower()->ascii()->toString();

        $modules = [];

        if (str_contains($text, 'fornecedor')) {
            $modules['categorias'] = [
                'name' => 'Categorias',
                'slug' => 'categorias',
                'label' => 'Categorias',
                'fields' => [
                    ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                    ['name' => 'descricao', 'type' => 'text', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'ativos', 'inativos'],
            ];

            $modules['fornecedores'] = [
                'name' => 'Fornecedores',
                'slug' => 'fornecedores',
                'label' => 'Fornecedores',
                'fields' => [
                    ['name' => 'categoria_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Categoria'],
                    ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                    ['name' => 'documento', 'type' => 'string', 'nullable' => true],
                    ['name' => 'email', 'type' => 'string', 'nullable' => true],
                    ['name' => 'telefone', 'type' => 'string', 'nullable' => true],
                    ['name' => 'cidade', 'type' => 'string', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'ativos', 'inativos', 'ultimos'],
            ];
        }

        if (str_contains($text, 'contrato')) {
            $modules['contratos'] = [
                'name' => 'Contratos',
                'slug' => 'contratos',
                'label' => 'Contratos',
                'fields' => [
                    ['name' => 'fornecedor_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Fornecedor'],
                    ['name' => 'numero', 'type' => 'string', 'nullable' => false],
                    ['name' => 'objeto', 'type' => 'text', 'nullable' => true],
                    ['name' => 'valor', 'type' => 'decimal', 'nullable' => true],
                    ['name' => 'data_inicio', 'type' => 'date', 'nullable' => true],
                    ['name' => 'data_fim', 'type' => 'date', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'ativos', 'valor_total'],
            ];
        }

        if (str_contains($text, 'documento')) {
            $modules['documentos'] = [
                'name' => 'Documentos',
                'slug' => 'documentos',
                'label' => 'Documentos',
                'fields' => [
                    ['name' => 'fornecedor_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Fornecedor'],
                    ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                    ['name' => 'tipo', 'type' => 'string', 'nullable' => true],
                    ['name' => 'arquivo', 'type' => 'string', 'nullable' => true],
                    ['name' => 'validade', 'type' => 'date', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'pendentes', 'aprovados'],
            ];
        }

        if ($modules === []) {
            $modules['registros'] = [
                'name' => 'Registros',
                'slug' => 'registros',
                'label' => 'Registros',
                'fields' => [
                    ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                    ['name' => 'descricao', 'type' => 'text', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'ativos', 'inativos'],
            ];
        }

        $slug = $this->slug(array_keys($modules));

        return [
            'name' => $this->title($modules),
            'slug' => $slug,
            'description' => $prompt,
            'modules' => array_values($modules),
        ];
    }

    protected function slug(array $modules): string
    {
        if (in_array('fornecedores', $modules, true) && in_array('contratos', $modules, true) && in_array('documentos', $modules, true)) {
            return 'gestao_fornecedores_contratos_documentos';
        }

        return 'gestao_' . implode('_', $modules);
    }

    protected function title(array $modules): string
    {
        if (isset($modules['fornecedores'], $modules['contratos'], $modules['documentos'])) {
            return 'Gestão de Fornecedores, Contratos e Documentos';
        }

        return 'Sistema Gerado pela Factory IA';
    }
}
