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
];
