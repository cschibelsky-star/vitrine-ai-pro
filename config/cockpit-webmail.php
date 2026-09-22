<?php

return [
    'enabled' => (bool) env('COCKPIT_WEBMAIL_ENABLED', false),

    'accounts' => [
        'vendas' => [
            'label' => 'Vitrine IA Pro - Vendas',
            'address' => env('COCKPIT_WEBMAIL_SALES_ADDRESS'),
            'provider' => env('COCKPIT_WEBMAIL_SALES_PROVIDER'),
            'imap_host' => env('COCKPIT_WEBMAIL_SALES_IMAP_HOST'),
            'imap_port' => (int) env('COCKPIT_WEBMAIL_SALES_IMAP_PORT', 993),
            'imap_encryption' => env('COCKPIT_WEBMAIL_SALES_IMAP_ENCRYPTION', 'ssl'),
            'smtp_host' => env('COCKPIT_WEBMAIL_SALES_SMTP_HOST'),
            'smtp_port' => (int) env('COCKPIT_WEBMAIL_SALES_SMTP_PORT', 465),
            'smtp_encryption' => env('COCKPIT_WEBMAIL_SALES_SMTP_ENCRYPTION', 'ssl'),
            'username' => env('COCKPIT_WEBMAIL_SALES_USERNAME'),
            'credential_secret' => 'COCKPIT_WEBMAIL_SALES_PASSWORD',
            'credential_secret_b64' => 'COCKPIT_WEBMAIL_SALES_PASSWORD_B64',
        ],
    ],
];
