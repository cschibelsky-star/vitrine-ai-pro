<?php

return [
    'evidence_directory' => env('PUBLICATION_EVIDENCE_DIRECTORY', storage_path('app/private/publication-evidence')),
    'ledger_directory' => env('PUBLICATION_LEDGER_DIRECTORY', storage_path('app/private/publication-ledger')),
    'collector_public_key' => env('PUBLICATION_COLLECTOR_PUBLIC_KEY', ''),
    'github_token' => env('PUBLICATION_GITHUB_TOKEN', ''),
    'webhook_secret' => env('PUBLICATION_GITHUB_WEBHOOK_SECRET', ''),
    'executor_token' => env('PUBLICATION_EXECUTOR_TOKEN', ''),
];
