<?php

return [
    // O laboratorio permanece independente. Nunca informar uma URL de producao sem homologacao.
    'base_url' => env('ROTEIA_LAB_FINANCE_BASE_URL', ''),
    'read_token' => env('ROTEIA_LAB_FINANCE_READ_TOKEN', ''),
    'allowed_host' => env('ROTEIA_LAB_FINANCE_ALLOWED_HOST', ''),
    'timeout' => 5,
];
