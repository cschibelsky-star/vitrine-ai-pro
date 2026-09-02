<?php

declare(strict_types=1);

namespace App\Shared\AI\Models;

use Illuminate\Database\Eloquent\Model;

class ViaOperationalSnapshot extends Model
{
    protected $fillable = [
        'domain',
        'source',
        'project_id',
        'status',
        'metrics',
        'evidence',
        'fingerprint',
        'collected_at',
    ];

    protected $casts = [
        'metrics' => 'array',
        'evidence' => 'array',
        'collected_at' => 'datetime',
    ];
}
