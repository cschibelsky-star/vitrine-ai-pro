<?php

declare(strict_types=1);

namespace App\Shared\AI\Models;

use App\Models\AiAgent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiProvider extends Model
{
    use HasFactory;

    protected $table = 'ai_providers';

    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function agents(): HasMany
    {
        return $this->hasMany(AiAgent::class, 'ai_provider_id');
    }
}
