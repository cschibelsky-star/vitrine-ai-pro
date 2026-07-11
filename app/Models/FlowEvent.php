<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_type',
        'source',
        'workflow',
        'execution_id',
        'status',
        'progress',
        'step',
        'message',
        'payload',
        'occurred_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'processed_at' => 'datetime',
            'progress' => 'integer',
        ];
    }
}
