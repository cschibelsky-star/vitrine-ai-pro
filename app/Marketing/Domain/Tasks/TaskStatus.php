<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Tasks;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Running = 'running';
    case Completed = 'completed';
    case NeedsRevision = 'needs_revision';
    case Blocked = 'blocked';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Skipped = 'skipped';
}
