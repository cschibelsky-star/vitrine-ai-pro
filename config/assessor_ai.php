<?php
return [
    // Enable only after isolated HML validates the existing G0–G7 gates.
    'enabled' => env('ASSESSOR_CENTRO_IA_ENABLED', false),
    'broker_url' => env('ASSESSOR_CENTRO_IA_BROKER_URL', ''),
    'token' => env('ASSESSOR_CENTRO_IA_TOKEN', ''),
];
