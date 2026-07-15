<?php

declare(strict_types=1);

namespace App\Core\Billing\Models;

use App\Models\Company;
use App\Models\License;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'next_due_date' => 'date',
        'activated_at' => 'datetime',
        'suspended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class);
    }
}
