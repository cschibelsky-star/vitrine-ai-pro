<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowSchedule extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'calendar' => 'array',
            'execution_window' => 'array',
            'holidays' => 'array',
            'payload' => 'array',
            'metadata' => 'array',
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'next_run_at' => 'datetime',
            'last_run_at' => 'datetime',
            'locked_until' => 'datetime',
            'is_active' => 'boolean',
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
