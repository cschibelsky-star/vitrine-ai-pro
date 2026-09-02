<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Persistence\Models;

use App\Marketing\Infrastructure\Persistence\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaignTask extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    public function agentDefinition(): BelongsTo
    {
        return $this->belongsTo(MarketingAgentDefinition::class, 'agent_definition_id');
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(MarketingTaskDependency::class, 'campaign_task_id');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(MarketingArtifactVersion::class, 'task_id');
    }
}
