<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Persistence\Models;

use App\Marketing\Infrastructure\Persistence\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingArtifactVersion extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'content' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $artifact): void {
            $artifact->checksum = hash('sha256', json_encode($artifact->content, JSON_THROW_ON_ERROR));
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaignTask::class, 'task_id');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(MarketingApproval::class, 'artifact_id');
    }
}
