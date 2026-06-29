<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Animai extends Model
{
    use SoftDeletes;

    protected $table = 'animais';

    protected $fillable = [
        'cliente_id',
        'nome',
        'especie',
        'raca',
        'status',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class);
    }
}
