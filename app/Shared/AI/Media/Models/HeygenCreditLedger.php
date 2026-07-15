<?php

declare(strict_types=1);

namespace App\Shared\AI\Media\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeygenCreditLedger extends Model
{
    protected $table = 'heygen_credit_ledgers';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(HeygenVideoJob::class, 'heygen_video_job_id');
    }
}
