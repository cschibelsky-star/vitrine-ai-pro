<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Campaigns;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Running = 'running';
    case Paused = 'paused';
    case Blocked = 'blocked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
