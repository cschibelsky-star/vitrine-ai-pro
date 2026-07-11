<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chave privada da API de captação
    |--------------------------------------------------------------------------
    |
    | Deve ser enviada pelos sites autorizados no cabeçalho
    | X-Vitrine-Lead-Key. Nunca exponha esta chave em JavaScript público.
    |
    */
    'key' => env('LEAD_CAPTURE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Consentimento LGPD
    |--------------------------------------------------------------------------
    */
    'require_consent' => (bool) env('LEAD_CAPTURE_REQUIRE_CONSENT', true),
];
