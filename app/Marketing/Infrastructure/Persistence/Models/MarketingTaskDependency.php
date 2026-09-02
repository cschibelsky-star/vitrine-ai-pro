<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Persistence\Models;

use App\Marketing\Infrastructure\Persistence\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingTaskDependency extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    public function task(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaignTask::class, 'campaign_task_id');
    }

    public function requiredTask(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaignTask::class, 'depends_on_task_id');
    }
}
