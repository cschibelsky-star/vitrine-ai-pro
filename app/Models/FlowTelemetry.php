<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowTelemetry extends Model
{
    use HasFactory;

    protected $table = 'flow_telemetry';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:6',
            'actual_cost' => 'decimal:6',
            'minutes' => 'decimal:4',
            'metrics' => 'array',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
