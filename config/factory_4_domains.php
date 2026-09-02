<?php

return [
    'veterinaria' => [
        'name' => 'Sistema para Clínicas Veterinárias',
        'slug' => 'clinicas_veterinarias',
        'keywords' => ['veterinaria', 'veterinario', 'clinica veterinaria', 'pet', 'animal', 'vacina', 'prontuario'],
        'modules' => [
            [
                'name' => 'Clientes',
                'slug' => 'clientes',
                'label' => 'Clientes',
                'fields' => [
                    ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                    ['name' => 'documento', 'type' => 'string', 'nullable' => true],
                    ['name' => 'telefone', 'type' => 'string', 'nullable' => true],
                    ['name' => 'email', 'type' => 'string', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'ativos'],
            ],
            [
                'name' => 'Animais',
                'slug' => 'animais',
                'label' => 'Animais',
                'fields' => [
                    ['name' => 'cliente_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Cliente'],
                    ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                    ['name' => 'especie', 'type' => 'string', 'nullable' => true],
                    ['name' => 'raca', 'type' => 'string', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'ativos'],
            ],
            [
                'name' => 'Agendamentos',
                'slug' => 'agendamentos',
                'label' => 'Agendamentos',
                'fields' => [
                    ['name' => 'animal_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Animal'],
                    ['name' => 'data_agendamento', 'type' => 'date', 'nullable' => true],
                    ['name' => 'tipo', 'type' => 'string', 'nullable' => true],
                    ['name' => 'observacoes', 'type' => 'text', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'hoje', 'pendentes'],
            ],
            [
                'name' => 'Prontuários',
                'slug' => 'prontuarios',
                'label' => 'Prontuários',
                'fields' => [
                    ['name' => 'animal_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Animal'],
                    ['name' => 'descricao', 'type' => 'text', 'nullable' => true],
                    ['name' => 'diagnostico', 'type' => 'text', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total'],
            ],
            [
                'name' => 'Vacinas',
                'slug' => 'vacinas',
                'label' => 'Vacinas',
                'fields' => [
                    ['name' => 'animal_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Animal'],
                    ['name' => 'nome', 'type' => 'string', 'nullable' => false],
                    ['name' => 'data_aplicacao', 'type' => 'date', 'nullable' => true],
                    ['name' => 'proxima_dose', 'type' => 'date', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'vencendo'],
            ],
            [
                'name' => 'Financeiro',
                'slug' => 'financeiro',
                'label' => 'Financeiro',
                'fields' => [
                    ['name' => 'cliente_id', 'type' => 'foreignId', 'nullable' => false, 'relationship' => 'belongsTo', 'related_model' => 'Cliente'],
                    ['name' => 'descricao', 'type' => 'string', 'nullable' => false],
                    ['name' => 'valor', 'type' => 'decimal', 'nullable' => true],
                    ['name' => 'vencimento', 'type' => 'date', 'nullable' => true],
                    ['name' => 'status', 'type' => 'string', 'nullable' => false],
                ],
                'dashboard_metrics' => ['total', 'valor_total', 'pendentes'],
            ],
        ],
    ],

    'patrimonio' => [
        'name' => 'Gestão de Patrimônio',
        'slug' => 'gestao_patrimonio',
        'keywords' => ['patrimonio', 'bens', 'inventario', 'manutencao', 'movimentacao'],
        'modules' => [
            ['name' => 'Categorias', 'slug' => 'categorias', 'label' => 'Categorias', 'fields' => [['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total']],
            ['name' => 'Bens', 'slug' => 'bens', 'label' => 'Bens', 'fields' => [['name'=>'categoria_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Categoria'],['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'patrimonio','type'=>'string','nullable'=>true],['name'=>'valor','type'=>'decimal','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','valor_total']],
            ['name' => 'Locais', 'slug' => 'locais', 'label' => 'Locais', 'fields' => [['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total']],
            ['name' => 'Movimentações', 'slug' => 'movimentacoes', 'label' => 'Movimentações', 'fields' => [['name'=>'bem_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Bem'],['name'=>'local_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Local'],['name'=>'data_movimentacao','type'=>'date','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total']],
        ],
    ],


    'guia_digital' => [
        'name' => 'Guia Digital da Cidade',
        'slug' => 'guia_digital_cidade',
        'keywords' => ['guia digital', 'guia da cidade', 'turismo', 'atrativo', 'roteiro turistico', 'conheca a cidade'],
        'modules' => [
            ['name' => 'Cidades', 'slug' => 'cidades', 'label' => 'Cidades', 'fields' => [['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'uf','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','ativas']],
            ['name' => 'Atrativos', 'slug' => 'atrativos', 'label' => 'Atrativos', 'fields' => [['name'=>'cidade_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Cidade'],['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'descricao','type'=>'text','nullable'=>true],['name'=>'endereco','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','publicados']],
            ['name' => 'Eventos', 'slug' => 'eventos', 'label' => 'Eventos', 'fields' => [['name'=>'cidade_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Cidade'],['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'data_inicio','type'=>'date','nullable'=>true],['name'=>'local','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','proximos']],
            ['name' => 'Empresas', 'slug' => 'empresas', 'label' => 'Empresas', 'fields' => [['name'=>'cidade_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Cidade'],['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'categoria','type'=>'string','nullable'=>true],['name'=>'whatsapp','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','ativas']],
            ['name' => 'Roteiros', 'slug' => 'roteiros', 'label' => 'Roteiros', 'fields' => [['name'=>'cidade_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Cidade'],['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'descricao','type'=>'text','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','publicados']],
            ['name' => 'Leads', 'slug' => 'leads', 'label' => 'Leads', 'fields' => [['name'=>'empresa','type'=>'string','nullable'=>false],['name'=>'responsavel','type'=>'string','nullable'=>false],['name'=>'email','type'=>'string','nullable'=>true],['name'=>'telefone','type'=>'string','nullable'=>true],['name'=>'consentimento_lgpd','type'=>'boolean','nullable'=>false],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','novos','convertidos']],
        ],
    ],

    'tv_digital' => [
        'name' => 'TV Digital Enterprise',
        'slug' => 'tv_digital_enterprise',
        'keywords' => ['tv digital', 'portal de noticias', 'portal news', 'televisao digital', 'reporter ia', 'tv online'],
        'modules' => [
            ['name' => 'Categorias', 'slug' => 'categorias', 'label' => 'Categorias', 'fields' => [['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total']],
            ['name' => 'Notícias', 'slug' => 'noticias', 'label' => 'Notícias', 'fields' => [['name'=>'categoria_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Categoria'],['name'=>'titulo','type'=>'string','nullable'=>false],['name'=>'conteudo','type'=>'text','nullable'=>true],['name'=>'publicado_em','type'=>'date','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','publicadas','revisao']],
            ['name' => 'Vídeos', 'slug' => 'videos', 'label' => 'Vídeos', 'fields' => [['name'=>'titulo','type'=>'string','nullable'=>false],['name'=>'url','type'=>'string','nullable'=>true],['name'=>'provedor','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','publicados','processando']],
            ['name' => 'Fontes RSS', 'slug' => 'fontes_rss', 'label' => 'Fontes RSS', 'fields' => [['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'url','type'=>'string','nullable'=>false],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','ativas']],
            ['name' => 'Anúncios', 'slug' => 'anuncios', 'label' => 'Anúncios', 'fields' => [['name'=>'empresa','type'=>'string','nullable'=>false],['name'=>'plano','type'=>'string','nullable'=>true],['name'=>'valor','type'=>'decimal','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','ativos','receita']],
            ['name' => 'Leads Comerciais', 'slug' => 'leads_comerciais', 'label' => 'Leads Comerciais', 'fields' => [['name'=>'empresa','type'=>'string','nullable'=>false],['name'=>'contato','type'=>'string','nullable'=>true],['name'=>'email','type'=>'string','nullable'=>true],['name'=>'telefone','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','novos','convertidos']],
        ],
    ],

    'assessorgov' => [
        'name' => 'AssessorGov IA',
        'slug' => 'assessorgov_ia',
        'keywords' => ['assessorgov', 'assessor gov', 'oportunidades publicas', 'terceiro setor', 'editais publicos'],
        'modules' => [
            ['name' => 'Clientes', 'slug' => 'clientes', 'label' => 'Clientes', 'fields' => [['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'segmento','type'=>'string','nullable'=>true],['name'=>'documento','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','ativos']],
            ['name' => 'Oportunidades', 'slug' => 'oportunidades', 'label' => 'Oportunidades', 'fields' => [['name'=>'titulo','type'=>'string','nullable'=>false],['name'=>'orgao','type'=>'string','nullable'=>true],['name'=>'prazo','type'=>'date','nullable'=>true],['name'=>'fonte_url','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','abertas','vencendo']],
            ['name' => 'Análises', 'slug' => 'analises', 'label' => 'Análises', 'fields' => [['name'=>'oportunidade_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Oportunidade'],['name'=>'resumo','type'=>'text','nullable'=>true],['name'=>'score','type'=>'decimal','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','aprovadas']],
            ['name' => 'Documentos', 'slug' => 'documentos', 'label' => 'Documentos', 'fields' => [['name'=>'cliente_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Cliente'],['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'arquivo','type'=>'string','nullable'=>true],['name'=>'validade','type'=>'date','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','vencendo']],
            ['name' => 'Alertas', 'slug' => 'alertas', 'label' => 'Alertas', 'fields' => [['name'=>'cliente_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Cliente'],['name'=>'mensagem','type'=>'text','nullable'=>false],['name'=>'canal','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','pendentes']],
        ],
    ],

    'govtech_compras' => [
        'name' => 'GovTech Compras IA',
        'slug' => 'govtech_compras_ia',
        'keywords' => ['govtech', 'compras publicas', 'licitacao', 'processo de compra', 'termo de referencia', 'etp', 'dfd'],
        'modules' => [
            ['name' => 'Processos', 'slug' => 'processos', 'label' => 'Processos', 'fields' => [['name'=>'objeto','type'=>'string','nullable'=>false],['name'=>'orgao','type'=>'string','nullable'=>true],['name'=>'modalidade','type'=>'string','nullable'=>true],['name'=>'valor_estimado','type'=>'decimal','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','em_analise','concluidos']],
            ['name' => 'Documentos', 'slug' => 'documentos', 'label' => 'Documentos', 'fields' => [['name'=>'processo_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Processo'],['name'=>'tipo','type'=>'string','nullable'=>false],['name'=>'arquivo','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','pendentes']],
            ['name' => 'Fornecedores', 'slug' => 'fornecedores', 'label' => 'Fornecedores', 'fields' => [['name'=>'nome','type'=>'string','nullable'=>false],['name'=>'documento','type'=>'string','nullable'=>true],['name'=>'email','type'=>'string','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','ativos']],
            ['name' => 'Revisões IA', 'slug' => 'revisoes_ia', 'label' => 'Revisões IA', 'fields' => [['name'=>'processo_id','type'=>'foreignId','nullable'=>false,'relationship'=>'belongsTo','related_model'=>'Processo'],['name'=>'parecer','type'=>'text','nullable'=>true],['name'=>'riscos','type'=>'text','nullable'=>true],['name'=>'score','type'=>'decimal','nullable'=>true],['name'=>'status','type'=>'string','nullable'=>false]], 'dashboard_metrics'=>['total','aprovadas','com_risco']],
        ],
    ],
];
