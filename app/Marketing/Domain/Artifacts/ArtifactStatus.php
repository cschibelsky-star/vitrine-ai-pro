<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Artifacts;

enum ArtifactStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Approved = 'approved';
    case Superseded = 'superseded';
}
