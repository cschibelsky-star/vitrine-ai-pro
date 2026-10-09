<?php

/**
 * Read-only registry for Cockpit HML links. Never consider these entries
 * proof of homologation: state is blocked until backed by verified evidence.
 * Add projects here as they are inventoried.
 */
return [
    'projects' => [
        [
            'id' => 'vitrine-site',
            'name' => 'Vitrine IA Pro — site comercial',
            'hml_url' => 'https://site.hml.vitrineiapro.com.br',
            'production_url' => 'https://vitrineaipro.com.br',
            'repository' => 'cschibelsky-star/vitrine-ai-pro',
            'candidate_ref' => 'main',
            'executor' => 'hostgator-public-site',
            'required_workflows' => ['.github/workflows/php-syntax.yml', '.github/workflows/publication-gate-tests.yml'],
            'required_steps' => [
                '.github/workflows/php-syntax.yml' => ['Validate PHP syntax'],
                '.github/workflows/publication-gate-tests.yml' => ['HTTP authorization regression tests', 'Run php tests/publication_gate.php'],
                '.github/workflows/calendar.yml' => ['Calendar regression checks'],
                '.github/workflows/conheca-sumare-acceptance.yml' => ['Acceptance tests'],
            ],
            'status' => 'bloqueada',
            'reason' => 'HML comercial não comprovada; rota existente não equivale a homologação deste produto.',
        ],
        [
            'id' => 'conheca-sumare',
            'name' => 'Conheça Sumaré',
            'hml_url' => 'https://p000002.hml.vitrineiapro.com.br',
            'production_url' => 'https://www.conhecasumare.com.br',
            'repository' => 'cschibelsky-star/vitrine-ai-pro',
            'candidate_ref' => 'deploy/conheca-sumare-hml',
            'executor' => 'conheca-sumare-vps',
            'required_workflows' => ['.github/workflows/calendar.yml', '.github/workflows/conheca-sumare-acceptance.yml'],
            'required_steps' => [
                '.github/workflows/php-syntax.yml' => ['Validate PHP syntax'],
                '.github/workflows/publication-gate-tests.yml' => ['HTTP authorization regression tests', 'Run php tests/publication_gate.php'],
                '.github/workflows/calendar.yml' => ['Calendar regression checks'],
                '.github/workflows/conheca-sumare-acceptance.yml' => ['Acceptance tests'],
            ],
            'status' => 'bloqueada',
            'reason' => 'Registry operacional aponta para visite-sumare; origem do runtime e evidências ainda precisam ser reconciliadas.',
        ],
    ],
];
