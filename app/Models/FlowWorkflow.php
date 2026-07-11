<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowWorkflow extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'workflow_key',
        'name',
        'version',
        'category',
        'owner',
        'queue',
        'priority',
        'sla_seconds',
        'timeout_seconds',
        'max_retries',
        'retry_backoff_seconds',
        'estimated_cost',
        'actual_cost',
        'default_provider',
        'n8n_workflow_id',
        'compatibility',
        'feature_flags',
        'metadata',
        'documentation',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'compatibility' => 'array',
            'feature_flags' => 'array',
            'metadata' => 'array',
            'estimated_cost' => 'decimal:6',
            'actual_cost' => 'decimal:6',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
