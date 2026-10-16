<?php
return [
    'enabled' => (bool) env('MARKETING_NATIVE_PUBLISHING_ENABLED', false),
    // Explicitly validated account capabilities only. No implicit enablement.
    'capabilities' => array_filter(explode(',', (string) env('MARKETING_NATIVE_PUBLISHING_CAPABILITIES', ''))),
];
