<?php

declare(strict_types=1);

namespace App\Factory\Blueprint\Services;

use App\Factory\Blueprint\DTO\BlueprintField;
use App\Factory\Blueprint\DTO\SystemBlueprint;
use App\Factory\Blueprint\DTO\SystemModuleBlueprint;

class DefaultBlueprintFactory
{
    public function make(string $slug): SystemBlueprint
    {
        return match ($slug) {
            'compras' => $this->compras(),
            'fornecedores' => $this->fornecedores(),
            default => $this->generic($slug),
        };
    }

    protected function compras(): SystemBlueprint
    {
        return new SystemBlueprint(
            name: 'Sistema de Compras',
            slug: 'compras',
            description: 'Sistema base com fornecedores, categorias, contratos, documentos e histórico.',
            modules: [
                new SystemModuleBlueprint(
                    name: 'Categorias',
                    slug: 'categorias',
                    label: 'Categorias',
                    fields: [
                        new BlueprintField('nome', 'string', false),
                        new BlueprintField('descricao', 'text'),
                        new BlueprintField('status', 'string', false),
                    ],
                    dashboardMetrics: ['total', 'ativos', 'inativos']
                ),
                new SystemModuleBlueprint(
                    name: 'Fornecedores',
                    slug: 'fornecedores',
                    label: 'Fornecedores',
                    fields: [
                        new BlueprintField('categoria_id', 'foreignId', false, 'belongsTo', 'Categoria'),
                        new BlueprintField('nome', 'string', false),
                        new BlueprintField('documento', 'string'),
                        new BlueprintField('email', 'string'),
                        new BlueprintField('telefone', 'string'),
                        new BlueprintField('cidade', 'string'),
                        new BlueprintField('status', 'string', false),
                    ],
                    dashboardMetrics: ['total', 'ativos', 'inativos', 'ultimos']
                ),
                new SystemModuleBlueprint(
                    name: 'Contratos',
                    slug: 'contratos',
                    label: 'Contratos',
                    fields: [
                        new BlueprintField('fornecedor_id', 'foreignId', false, 'belongsTo', 'Fornecedor'),
                        new BlueprintField('numero', 'string', false),
                        new BlueprintField('objeto', 'text'),
                        new BlueprintField('valor', 'decimal'),
                        new BlueprintField('data_inicio', 'date'),
                        new BlueprintField('data_fim', 'date'),
                        new BlueprintField('status', 'string', false),
                    ],
                    dashboardMetrics: ['total', 'ativos', 'valor_total']
                ),
                new SystemModuleBlueprint(
                    name: 'Documentos',
                    slug: 'documentos',
                    label: 'Documentos',
                    fields: [
                        new BlueprintField('fornecedor_id', 'foreignId', false, 'belongsTo', 'Fornecedor'),
                        new BlueprintField('nome', 'string', false),
                        new BlueprintField('tipo', 'string'),
                        new BlueprintField('arquivo', 'string'),
                        new BlueprintField('status', 'string', false),
                    ],
                    dashboardMetrics: ['total', 'pendentes', 'aprovados']
                ),
                new SystemModuleBlueprint(
                    name: 'Historicos',
                    slug: 'historicos',
                    label: 'Históricos',
                    fields: [
                        new BlueprintField('fornecedor_id', 'foreignId', false, 'belongsTo', 'Fornecedor'),
                        new BlueprintField('descricao', 'text', false),
                        new BlueprintField('tipo', 'string'),
                        new BlueprintField('data_registro', 'date'),
                    ],
                    dashboardMetrics: ['total', 'ultimos']
                ),
            ]
        );
    }

    protected function fornecedores(): SystemBlueprint
    {
        return new SystemBlueprint(
            name: 'Sistema de Gestão de Fornecedores',
            slug: 'fornecedores',
            description: 'Solução base para gestão de fornecedores.',
            modules: [
                new SystemModuleBlueprint(
                    name: 'Fornecedores',
                    slug: 'fornecedores',
                    label: 'Fornecedores',
                    fields: [
                        new BlueprintField('nome', 'string', false),
                        new BlueprintField('documento', 'string'),
                        new BlueprintField('email', 'string'),
                        new BlueprintField('telefone', 'string'),
                        new BlueprintField('cidade', 'string'),
                        new BlueprintField('status', 'string', false),
                    ],
                    dashboardMetrics: ['total', 'ativos', 'inativos', 'ultimos']
                ),
            ]
        );
    }

    protected function generic(string $slug): SystemBlueprint
    {
        return new SystemBlueprint(
            name: ucfirst($slug),
            slug: $slug,
            description: 'Blueprint genérico criado pela Factory.',
            modules: [
                new SystemModuleBlueprint(
                    name: ucfirst($slug),
                    slug: $slug,
                    label: ucfirst($slug),
                    fields: [
                        new BlueprintField('nome', 'string', false),
                        new BlueprintField('descricao', 'text'),
                        new BlueprintField('status', 'string', false),
                    ],
                    dashboardMetrics: ['total', 'ativos', 'inativos']
                ),
            ]
        );
    }
}
