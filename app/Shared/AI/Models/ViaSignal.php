<?php

declare(strict_types=1);

namespace App\Shared\AI\Models;

use Illuminate\Database\Eloquent\Model;

class ViaSignal extends Model
{
    protected $fillable = [
        'type',
        'domain',
        'project_id',
        'source',
        'severity',
        'confidence',
        'title',
        'description',
        'evidence',
        'fingerprint',
        'occurrences',
        'status',
        'first_seen_at',
        'last_seen_at',
        'resolved_at',
    ];

    protected $casts = [
        'confidence' => 'float',
        'evidence' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}
