<?php

declare(strict_types=1);

namespace App\Shared\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViaConversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'domain',
        'target_project_id',
        'mode',
        'last_activity_at',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ViaMessage::class)->orderBy('id');
    }
}
