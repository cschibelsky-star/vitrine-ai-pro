<?php

return [
    'version' => '1.0.0',
    'name' => 'Factory Production Engine',
    'status' => 'active',
    'products' => [
        'gov360' => [
            'name' => 'Consultor AI GOV360',
            'prompt' => 'Crie o Consultor AI GOV360 para pequenas empresas venderem mais para o governo.',
            'domain' => 'governo digital',
        ],
        'guia_digital' => [
            'name' => 'Guia Digital da Cidade',
            'prompt' => 'Crie o Guia Digital da Cidade para turismo, eventos, atrativos e comércio local.',
            'domain' => 'turismo guia digital',
        ],
        'portal_news' => [
            'name' => 'Portal News AI',
            'prompt' => 'Crie um portal de notícias automatizado com RSS, revisão editorial, vídeos e banners.',
            'domain' => 'portal news',
        ],
        'tv_digital' => [
            'name' => 'TV Digital Enterprise',
            'prompt' => 'Crie uma TV digital enterprise com vídeos, ao vivo, playlists, repórter IA e patrocinadores.',
            'domain' => 'tv digital',
        ],
        'sismed' => [
            'name' => 'SISMED',
            'prompt' => 'Crie o SISMED para saúde pública com pacientes, unidades, atendimentos e documentos.',
            'domain' => 'saúde sismed',
        ],
    ],
];
