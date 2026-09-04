<?php

declare(strict_types=1);

namespace App\Factory\AI\Services;

class DomainKnowledgeBase
{
    public function match(string $normalizedText): string
    {
        $fundingSignals = [
            'captacao',
            'recursos',
            'financiamento',
            'oportunidade',
            'oportunidades',
            'edital',
            'aderencia',
            'candidatura',
            'matching',
        ];

        $fundingScore = collect($fundingSignals)
            ->filter(fn (string $signal): bool => str_contains($normalizedText, $signal))
            ->count();

        if ($fundingScore >= 2 || str_contains($normalizedText, 'captacao de recursos')) {
            return 'captacao_recursos';
        }

        if (str_contains($normalizedText, 'licitacao') || str_contains($normalizedText, 'licitações') || str_contains($normalizedText, 'licitacoes')) {
            return 'licitacoes';
        }

        if (str_contains($normalizedText, 'patrimonio') || str_contains($normalizedText, 'bens') || str_contains($normalizedText, 'inventario')) {
            return 'patrimonio';
        }

        if (str_contains($normalizedText, 'crm') || str_contains($normalizedText, 'vendas') || str_contains($normalizedText, 'leads')) {
            return 'crm';
        }

        if (str_contains($normalizedText, 'saude') || str_contains($normalizedText, 'sismed') || str_contains($normalizedText, 'paciente')) {
            return 'saude';
        }

        if (str_contains($normalizedText, 'turismo') || str_contains($normalizedText, 'guia') || str_contains($normalizedText, 'cidade')) {
            return 'turismo';
        }

        if (str_contains($normalizedText, 'fornecedor') || str_contains($normalizedText, 'contrato') || str_contains($normalizedText, 'documento')) {
            return 'fornecedores';
        }

        return 'generico';
    }

    public function blueprintFor(string $domain, string $prompt): array
    {
        return match ($domain) {
            'captacao_recursos' => $this->captacaoRecursos($prompt),
            'licitacoes' => $this->licitacoes($prompt),
            'patrimonio' => $this->patrimonio($prompt),
            'crm' => $this->crm($prompt),
            'saude' => $this->saude($prompt),
            'turismo' => $this->turismo($prompt),
            'fornecedores' => $this->fornecedores($prompt),
            default => $this->generico($prompt),
        };
    }

    protected function licitacoes(string $prompt): array
    {
        return [
            'name' => 'Gestão de Licitações Públicas',
            'slug' => 'gestao_licitacoes_publicas',
            'description' => $prompt,
            'modules' => [
                $this->categorias(),
                [
                    'name' => 'Fornecedores',
                    'slug' => 'fornecedores',
                    'label' => 'Fornecedores',
                    'fields' => [
                        $this->foreign('categoria_id', 'Categoria'),
                        $this->field('nome', false),
                        $this->field('documento'),
                        $this->field('email'),
                        $this->field('telefone'),
                        $this->field('cidade'),
                        $this->field('status', false),
                    ],
                    'dashboard_metrics' => ['total', 'ativos', 'inativos'],
                ],
                [
                    'name' => 'Licitações',
                    'slug' => 'licitacoes',
                    'label' => 'Licitações',
                    'fields' => [
                        $this->field('numero', false),
                        $this->field('objeto', true, 'text'),
                        $this->field('modalidade'),
                        $this->field('data_abertura', true, 'date'),
                        $this->field('valor_estimado', true, 'decimal'),
                        $this->field('status', false),
                    ],
                    'dashboard_metrics' => ['total', 'abertas', 'em_andamento', 'finalizadas'],
                ],
                [
                    'name' => 'Propostas',
                    'slug' => 'propostas',
                    'label' => 'Propostas',
                    'fields' => [
                        $this->foreign('licitacao_id', 'Licitacao'),
                        $this->foreign('fornecedor_id', 'Fornecedor'),
                        $this->field('valor', true, 'decimal'),
                        $this->field('status', false),
                        $this->field('observacoes', true, 'text'),
                    ],
                    'dashboard_metrics' => ['total', 'vencedoras', 'em_analise'],
                ],
                [
                    'name' => 'Contratos',
                    'slug' => 'contratos',
                    'label' => 'Contratos',
                    'fields' => [
                        $this->foreign('fornecedor_id', 'Fornecedor'),
                        $this->foreign('licitacao_id', 'Licitacao'),
                        $this->field('numero', false),
                        $this->field('objeto', true, 'text'),
                        $this->field('valor', true, 'decimal'),
                        $this->field('data_inicio', true, 'date'),
                        $this->field('data_fim', true, 'date'),
                        $this->field('status', false),
                    ],
                    'dashboard_metrics' => ['total', 'ativos', 'valor_total'],
                ],
                [
                    'name' => 'Documentos',
                    'slug' => 'documentos',
                    'label' => 'Documentos',
                    'fields' => [
                        $this->foreign('licitacao_id', 'Licitacao'),
                        $this->field('nome', false),
                        $this->field('tipo'),
                        $this->field('arquivo'),
                        $this->field('status', false),
                    ],
                    'dashboard_metrics' => ['total', 'pendentes', 'aprovados'],
                ],
            ],
        ];
    }

    protected function captacaoRecursos(string $prompt): array
    {
        return [
            'name' => 'Captação de Recursos e Oportunidades',
            'slug' => 'captacao_recursos_oportunidades',
            'description' => $prompt,
            'modules' => [
                [
                    'name' => 'Perfis',
                    'slug' => 'perfis',
                    'label' => 'Perfil/DNA',
                    'fields' => [
                        $this->field('nome', false),
                        $this->field('tipo_organizacao'),
                        $this->field('areas_atuacao', true, 'text'),
                        $this->field('publico_atendido', true, 'text'),
                        $this->field('territorio'),
                        $this->field('status', false),
                    ],
                    'dashboard_metrics' => ['total', 'ativos'],
                ],
                [
                    'name' => 'Oportunidades',
                    'slug' => 'oportunidades',
                    'label' => 'Oportunidades',
                    'fields' => [
                        $this->field('titulo', false),
                        $this->field('fonte'),
                        $this->field('tipo'),
                        $this->field('prazo', true, 'date'),
                        $this->field('valor_estimado', true, 'decimal'),
                        $this->field('requisitos', true, 'text'),
                        $this->field('status', false),
                    ],
                    'dashboard_metrics' => ['total', 'abertas', 'prioritarias'],
                ],
                [
                    'name' => 'Matching',
                    'slug' => 'matching',
                    'label' => 'Matching',
                    'fields' => [
                        $this->foreign('perfil_id', 'Perfil'),
                        $this->foreign('oportunidade_id', 'Oportunidade'),
                        $this->field('score', true, 'decimal'),
                        $this->field('aderencia', true, 'text'),
                        $this->field('status', false),
                    ],
                    'dashboard_metrics' => ['total', 'alta_aderencia'],
                ],
                [
                    'name' => 'Analises',
                    'slug' => 'analises',
                    'label' => 'Análises',
                    'fields' => [
                        $this->foreign('oportunidade_id', 'Oportunidade'),
                        $this->field('resumo', true, 'text'),
                        $this->field('riscos', true, 'text'),
                        $this->field('recomendacao', true, 'text'),
                        $this->field('status', false),
                    ],
                    'dashboard_metrics' => ['total', 'recomendadas'],
                ],
                [
                    'name' => 'Documentos',
                    'slug' => 'documentos',
                    'label' => 'Documentos e Lacunas',
                    'fields' => [
                        $this->foreign('oportunidade_id', 'Oportunidade'),
                        $this->field('nome', false),
                        $this->field('tipo'),
                        $this->field('obrigatorio', true, 'boolean'),
                        $this->field('status', false),
                        $this->field('lacuna', true, 'text'),
                    ],
                    'dashboard_metrics' => ['total', 'pendentes', 'lacunas'],
                ],
                [
                    'name' => 'PlanosDeAcao',
                    'slug' => 'planos_acao',
                    'label' => 'Planos de Ação',
                    'fields' => [
                        $this->foreign('oportunidade_id', 'Oportunidade'),
                        $this->field('acao', false, 'text'),
                        $this->field('responsavel'),
                        $this->field('prazo', true, 'date'),
                        $this->field('status', false),
                    ],
                    'dashboard_metrics' => ['total', 'pendentes', 'concluidos'],
                ],
            ],
        ];
    }

    protected function patrimonio(string $prompt): array
    {
        return [
            'name' => 'Gestão de Patrimônio',
            'slug' => 'gestao_patrimonio',
            'description' => $prompt,
            'modules' => [
                $this->categorias(),
                ['name' => 'Locais', 'slug' => 'locais', 'label' => 'Locais', 'fields' => [$this->field('nome', false), $this->field('descricao', true, 'text'), $this->field('status', false)], 'dashboard_metrics' => ['total']],
                ['name' => 'Bens', 'slug' => 'bens', 'label' => 'Bens', 'fields' => [$this->foreign('categoria_id', 'Categoria'), $this->foreign('local_id', 'Local'), $this->field('codigo', false), $this->field('nome', false), $this->field('valor', true, 'decimal'), $this->field('data_aquisicao', true, 'date'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'ativos', 'baixados', 'valor_total']],
                ['name' => 'Movimentações', 'slug' => 'movimentacoes', 'label' => 'Movimentações', 'fields' => [$this->foreign('bem_id', 'Bem'), $this->field('tipo', false), $this->field('descricao', true, 'text'), $this->field('data_movimentacao', true, 'date')], 'dashboard_metrics' => ['total', 'ultimas']],
            ],
        ];
    }

    protected function crm(string $prompt): array
    {
        return [
            'name' => 'CRM Comercial',
            'slug' => 'crm_comercial',
            'description' => $prompt,
            'modules' => [
                ['name' => 'Clientes', 'slug' => 'clientes', 'label' => 'Clientes', 'fields' => [$this->field('nome', false), $this->field('documento'), $this->field('email'), $this->field('telefone'), $this->field('cidade'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'ativos']],
                ['name' => 'Leads', 'slug' => 'leads', 'label' => 'Leads', 'fields' => [$this->field('nome', false), $this->field('email'), $this->field('telefone'), $this->field('origem'), $this->field('status', false), $this->field('proxima_acao', true, 'date')], 'dashboard_metrics' => ['total', 'novos', 'negociacao']],
                ['name' => 'Propostas', 'slug' => 'propostas', 'label' => 'Propostas', 'fields' => [$this->foreign('cliente_id', 'Cliente'), $this->field('titulo', false), $this->field('valor', true, 'decimal'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'valor_total']],
            ],
        ];
    }

    protected function saude(string $prompt): array
    {
        return [
            'name' => 'SISMED Base',
            'slug' => 'sismed_base',
            'description' => $prompt,
            'modules' => [
                ['name' => 'Pacientes', 'slug' => 'pacientes', 'label' => 'Pacientes', 'fields' => [$this->field('nome', false), $this->field('documento'), $this->field('telefone'), $this->field('data_nascimento', true, 'date'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'ativos']],
                ['name' => 'Unidades', 'slug' => 'unidades', 'label' => 'Unidades', 'fields' => [$this->field('nome', false), $this->field('endereco'), $this->field('cidade'), $this->field('status', false)], 'dashboard_metrics' => ['total']],
                ['name' => 'Atendimentos', 'slug' => 'atendimentos', 'label' => 'Atendimentos', 'fields' => [$this->foreign('paciente_id', 'Paciente'), $this->foreign('unidade_id', 'Unidade'), $this->field('data_atendimento', true, 'date'), $this->field('descricao', true, 'text'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'hoje', 'mes']],
            ],
        ];
    }

    protected function turismo(string $prompt): array
    {
        return [
            'name' => 'Guia Digital da Cidade',
            'slug' => 'guia_digital_cidade',
            'description' => $prompt,
            'modules' => [
                ['name' => 'Cidades', 'slug' => 'cidades', 'label' => 'Cidades', 'fields' => [$this->field('nome', false), $this->field('estado'), $this->field('descricao', true, 'text'), $this->field('status', false)], 'dashboard_metrics' => ['total']],
                ['name' => 'Atrativos', 'slug' => 'atrativos', 'label' => 'Atrativos', 'fields' => [$this->foreign('cidade_id', 'Cidade'), $this->field('nome', false), $this->field('tipo'), $this->field('descricao', true, 'text'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'ativos']],
                ['name' => 'Eventos', 'slug' => 'eventos', 'label' => 'Eventos', 'fields' => [$this->foreign('cidade_id', 'Cidade'), $this->field('nome', false), $this->field('data_evento', true, 'date'), $this->field('descricao', true, 'text'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'proximos']],
            ],
        ];
    }

    protected function fornecedores(string $prompt): array
    {
        return [
            'name' => 'Gestão de Fornecedores, Contratos e Documentos',
            'slug' => 'gestao_fornecedores_contratos_documentos',
            'description' => $prompt,
            'modules' => [
                $this->categorias(),
                ['name' => 'Fornecedores', 'slug' => 'fornecedores', 'label' => 'Fornecedores', 'fields' => [$this->foreign('categoria_id', 'Categoria'), $this->field('nome', false), $this->field('documento'), $this->field('email'), $this->field('telefone'), $this->field('cidade'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'ativos', 'inativos']],
                ['name' => 'Contratos', 'slug' => 'contratos', 'label' => 'Contratos', 'fields' => [$this->foreign('fornecedor_id', 'Fornecedor'), $this->field('numero', false), $this->field('objeto', true, 'text'), $this->field('valor', true, 'decimal'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'valor_total']],
                ['name' => 'Documentos', 'slug' => 'documentos', 'label' => 'Documentos', 'fields' => [$this->foreign('fornecedor_id', 'Fornecedor'), $this->field('nome', false), $this->field('tipo'), $this->field('arquivo'), $this->field('status', false)], 'dashboard_metrics' => ['total']],
            ],
        ];
    }

    protected function generico(string $prompt): array
    {
        return [
            'name' => 'Sistema Gerado pela Factory',
            'slug' => 'sistema_gerado_factory',
            'description' => $prompt,
            'modules' => [
                ['name' => 'Registros', 'slug' => 'registros', 'label' => 'Registros', 'fields' => [$this->field('nome', false), $this->field('descricao', true, 'text'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'ativos']],
            ],
        ];
    }

    protected function categorias(): array
    {
        return ['name' => 'Categorias', 'slug' => 'categorias', 'label' => 'Categorias', 'fields' => [$this->field('nome', false), $this->field('descricao', true, 'text'), $this->field('status', false)], 'dashboard_metrics' => ['total', 'ativos']];
    }

    protected function field(string $name, bool $nullable = true, string $type = 'string'): array
    {
        return ['name' => $name, 'type' => $type, 'nullable' => $nullable];
    }

    protected function foreign(string $name, string $relatedModel): array
    {
        return ['name' => $name, 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => $relatedModel];
    }
}
