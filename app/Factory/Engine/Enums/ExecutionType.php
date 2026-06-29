<?php

declare(strict_types=1);

namespace App\Factory\Engine\Enums;

enum ExecutionType: string
{
    case Manual = 'manual';
    case Queue = 'queue';
    case Scheduled = 'scheduled';
    case Api = 'api';
}
