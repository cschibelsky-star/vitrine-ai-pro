<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeygenVideoJob extends Model
{
    protected $table = 'heygen_video_jobs';
    protected $guarded = [];

    protected $casts = [
        'credits_used' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function agent(): BelongsTo { return $this->belongsTo(AiAgent::class, 'ai_agent_id'); }
    public function provider(): BelongsTo { return $this->belongsTo(AiProvider::class, 'ai_provider_id'); }
    public function avatar(): BelongsTo { return $this->belongsTo(HeygenAvatar::class, 'heygen_avatar_id'); }
}
