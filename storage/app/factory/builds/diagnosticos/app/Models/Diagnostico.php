<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Diagnostico extends Model
{
    use SoftDeletes;

    protected $table = 'diagnosticos';

    protected $fillable = [
        'cliente_id',
        'titulo',
        'descricao',
        'score',
        'status',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class);
    }
}
