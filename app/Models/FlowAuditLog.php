<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'workflow_uuid',
        'execution_uuid',
        'trace_id',
        'correlation_id',
        'event_type',
        'actor_type',
        'actor_id',
        'source',
        'ip_address',
        'user_agent',
        'context',
        'before',
        'after',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'before' => 'array',
            'after' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
