<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HeygenAvatar extends Model
{
    protected $table = 'heygen_avatars';
    protected $guarded = [];

    public function jobs(): HasMany
    {
        return $this->hasMany(HeygenVideoJob::class);
    }
}
