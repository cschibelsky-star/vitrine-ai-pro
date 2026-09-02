<?php

declare(strict_types=1);

namespace App\Shared\AI\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViaMessage extends Model
{
    protected $fillable = [
        'via_conversation_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ViaConversation::class, 'via_conversation_id');
    }
}
