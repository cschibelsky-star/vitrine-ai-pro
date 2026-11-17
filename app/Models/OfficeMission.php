<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeMission extends Model
{
    protected $table = 'office_missions';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'created_by', 'domain', 'title', 'description',
        'status', 'priority', 'idempotency_key', 'metadata',
    ];

    protected $casts = ['metadata' => 'array'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(OfficeMissionEvent::class, 'mission_id')->orderBy('created_at');
    }
}
