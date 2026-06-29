<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Relatorio extends Model
{
    use SoftDeletes;

    protected $table = 'relatorios';

    protected $fillable = [
        'cliente_id',
        'titulo',
        'conteudo',
        'status',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class);
    }
}
