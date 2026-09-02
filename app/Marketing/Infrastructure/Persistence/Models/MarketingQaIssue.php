<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Persistence\Models;

use App\Marketing\Infrastructure\Persistence\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingQaIssue extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    public function qaTask(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaignTask::class, 'qa_task_id');
    }

    public function targetTask(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaignTask::class, 'target_task_id');
    }

    public function targetArtifact(): BelongsTo
    {
        return $this->belongsTo(MarketingArtifactVersion::class, 'target_artifact_id');
    }
}
