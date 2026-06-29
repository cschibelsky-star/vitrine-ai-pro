<?php

return [
    'token' => env('SITE_FACTORY_TOKEN', null),

    'allowed_products' => [
        'TV Digital Enterprise',
        'Guia Digital da Cidade',
        'Consultor AI GOV360',
        'Portal News AI',
        'SISMED',
    ],

    'default_plan' => 'start',

    'dry_run_default' => true,
];
