<?php

declare(strict_types=1);

namespace App\Marketing\Infrastructure\Persistence\Models;

use App\Marketing\Infrastructure\Persistence\Models\Concerns\BelongsToCompany;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MarketingCampaign extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'known_facts' => 'array',
        'missing_information' => 'array',
        'restrictions' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $campaign): void {
            $campaign->public_id ??= (string) Str::ulid();
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(MarketingCampaignTask::class, 'campaign_id');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(MarketingArtifactVersion::class, 'campaign_id');
    }
}
