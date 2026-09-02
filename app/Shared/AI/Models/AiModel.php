<?php

declare(strict_types=1);

namespace App\Shared\AI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiModel extends Model
{
    use HasFactory;

    protected $table = 'ai_models';

    protected $guarded = [];

    protected $casts = [
        'capabilities' => 'array',
        'metadata' => 'array',
        'input_cost_per_million' => 'decimal:6',
        'output_cost_per_million' => 'decimal:6',
        'unit_cost_brl' => 'decimal:6',
        'context_window' => 'integer',
        'is_active' => 'boolean',
        'is_experimental' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }
}
