<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animal extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cliente_id',
        'nome',
        'especie',
        'raca',
        'status'
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class);
    }
}
