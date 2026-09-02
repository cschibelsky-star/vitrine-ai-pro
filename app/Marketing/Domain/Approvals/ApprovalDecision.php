<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Approvals;

enum ApprovalDecision: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case RevisionRequested = 'revision_requested';
    case Rejected = 'rejected';
}
