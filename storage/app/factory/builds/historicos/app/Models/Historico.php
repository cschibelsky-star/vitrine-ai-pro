<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Historico extends Model
{
    use SoftDeletes;

    protected $table = 'historicos';

    protected $fillable = [
        'fornecedor_id',
        'descricao',
        'tipo',
        'data_registro',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(\App\Models\Fornecedor::class);
    }
}
