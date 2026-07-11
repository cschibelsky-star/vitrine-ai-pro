<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowComplianceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'company_id',
        'request_type',
        'subject_type',
        'subject_reference',
        'legal_basis',
        'status',
        'retention_days',
        'due_at',
        'processed_at',
        'requested_by',
        'processed_by',
        'reason',
        'scope',
        'result',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'result' => 'array',
            'metadata' => 'array',
            'due_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
