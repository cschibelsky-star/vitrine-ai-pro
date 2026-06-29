<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contrato extends Model
{
    use SoftDeletes;

    protected $table = 'contratos';

    protected $fillable = [
        'fornecedor_id',
        'licitacao_id',
        'numero',
        'objeto',
        'valor',
        'data_inicio',
        'data_fim',
        'status',
    ];

    public function fornecedor()
    {
        return $this->belongsTo(\App\Models\Fornecedor::class);
    }

    public function licitacao()
    {
        return $this->belongsTo(\App\Models\Licitacao::class);
    }
}
