<?php

/**
 * Read-only registry for Cockpit HML links. Never consider these entries
 * proof of homologation: state is blocked until backed by verified evidence.
 * Add projects here as they are inventoried.
 */
return [
    'projects' => [
        [
            'id' => 'conheca-sumare',
            'name' => 'Conheça Sumaré',
            'hml_url' => 'https://conheca-sumare-hml.vitrineaipro.com.br',
            'production_url' => 'https://www.conhecasumare.com.br',
            'status' => 'bloqueada',
            'reason' => 'DNS HML e isolamento não validados; aprovação e testes pendentes.',
        ],
    ],
];
