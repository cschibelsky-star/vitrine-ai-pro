<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function licenses(): HasMany { return $this->hasMany(License::class); }
    public function plans(): HasMany { return $this->hasMany(Plan::class); }
    public function modules(): HasMany { return $this->hasMany(Module::class); }
    public function contracts(): HasMany { return $this->hasMany(Contract::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
}
