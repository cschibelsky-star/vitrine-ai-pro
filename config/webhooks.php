<?php

return [
    'asaas' => [
        'token' => env('ASAAS_WEBHOOK_TOKEN'),
        'headers' => ['asaas-access-token', 'x-webhook-token'],
    ],
    'heygen' => [
        'token' => env('HEYGEN_WEBHOOK_TOKEN'),
        'headers' => ['x-heygen-webhook-token', 'x-webhook-token'],
    ],
];
