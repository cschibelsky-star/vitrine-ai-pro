<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeMissionEvent extends Model
{
    protected $table = 'office_mission_events';

    protected $fillable = [
        'mission_id', 'actor_id', 'event_type', 'from_status',
        'to_status', 'request_id', 'payload',
    ];

    protected $casts = ['payload' => 'array'];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(OfficeMission::class, 'mission_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
