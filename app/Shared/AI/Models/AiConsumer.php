<?php

namespace App\Shared\AI\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConsumer extends Model
{
    use HasFactory;

    protected $table = 'ai_consumers';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function consumptions(): HasMany
    {
        return $this->hasMany(AiConsumption::class, 'ai_consumer_id');
    }
}
