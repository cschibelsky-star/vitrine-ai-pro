<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlowSecret extends Model
{
    protected $fillable = [
        'uuid',
        'company_id',
        'key',
        'encrypted_value',
        'scope',
        'status',
        'expires_at',
        'last_accessed_at',
        'metadata',
    ];

    protected $hidden = ['encrypted_value'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_accessed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
