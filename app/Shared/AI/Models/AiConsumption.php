<?php

namespace App\Shared\AI\Models;

use App\Models\Company;
use App\Models\License;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConsumption extends Model
{
    use HasFactory;

    protected $table = 'ai_consumptions';

    protected $guarded = [];

    protected $casts = [
        'consumption_date' => 'date',
        'occurred_at' => 'datetime',
        'quantity' => 'decimal:4',
        'input_units' => 'decimal:4',
        'output_units' => 'decimal:4',
        'estimated_cost' => 'decimal:4',
        'actual_cost' => 'decimal:6',
        'original_cost' => 'decimal:6',
        'fx_rate' => 'decimal:6',
        'cost_brl' => 'decimal:6',
        'metadata' => 'array',
    ];

    public function consumer(): BelongsTo
    {
        return $this->belongsTo(AiConsumer::class, 'ai_consumer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'ai_agent_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class, 'ai_provider_id');
    }
}
