<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowFeatureFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'key',
        'company_id',
        'plan_id',
        'workflow_uuid',
        'enabled',
        'beta',
        'rollout_percentage',
        'priority',
        'config',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'beta' => 'boolean',
            'rollout_percentage' => 'integer',
            'priority' => 'integer',
            'config' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
