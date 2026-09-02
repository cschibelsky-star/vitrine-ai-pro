<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Persistence\Models;

use App\Marketing\Infrastructure\Persistence\Models\Concerns\BelongsToCompany;
use App\Shared\AI\Models\AiAgent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingAgentDefinition extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'depends_on' => 'array',
        'permissions' => 'array',
        'enabled' => 'boolean',
        'may_block_pipeline' => 'boolean',
    ];

    public function aiAgent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MarketingCampaignTask::class, 'agent_definition_id');
    }
}
