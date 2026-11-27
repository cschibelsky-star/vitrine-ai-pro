<?php

return [
    'via_cockpit' => (bool) env('ASSESSOR_HML_INVITE_VIA_COCKPIT', false),
    'secret' => env('ASSESSOR_HML_INVITE_GATEWAY_SECRET'),
];
