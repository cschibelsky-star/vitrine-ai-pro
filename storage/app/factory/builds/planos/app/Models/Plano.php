<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plano extends Model
{
    use SoftDeletes;

    protected $table = 'planos';

    protected $fillable = [
        'cliente_id',
        'titulo',
        'descricao',
        'prazo',
        'status',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class);
    }
}
