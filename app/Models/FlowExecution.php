<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'flow_workflow_id',
        'workflow_uuid',
        'trace_id',
        'correlation_id',
        'status',
        'queue',
        'priority',
        'provider',
        'lock_owner',
        'usage_reservation_uuid',
        'input',
        'context',
        'output',
        'failure_reason',
        'attempts',
        'queued_at',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'input' => 'array',
            'context' => 'array',
            'output' => 'array',
            'priority' => 'integer',
            'attempts' => 'integer',
            'queued_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(FlowWorkflow::class, 'flow_workflow_id');
    }
}
