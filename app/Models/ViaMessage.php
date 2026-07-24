<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViaMessage extends Model
{
    protected $fillable = [
        'via_conversation_id',
        'role',
        'content',
        'status',
        'provider',
        'model',
        'tokens_input',
        'tokens_output',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'tokens_input' => 'integer',
        'tokens_output' => 'integer',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ViaConversation::class, 'via_conversation_id');
    }
}
