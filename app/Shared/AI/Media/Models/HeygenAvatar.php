<?php

declare(strict_types=1);

namespace App\Shared\AI\Media\Models;

use App\Models\HeygenVideoJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HeygenAvatar extends Model
{
    protected $table = 'heygen_avatars';

    protected $guarded = [];

    public function jobs(): HasMany
    {
        return $this->hasMany(HeygenVideoJob::class, 'heygen_avatar_id');
    }
}
