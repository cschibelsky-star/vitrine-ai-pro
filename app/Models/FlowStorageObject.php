<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowStorageObject extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'workflow_uuid',
        'execution_id',
        'disk',
        'path',
        'visibility',
        'mime_type',
        'size_bytes',
        'checksum',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
