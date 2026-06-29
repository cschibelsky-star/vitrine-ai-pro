<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposta extends Model
{
    use SoftDeletes;

    protected $table = 'propostas';

    protected $fillable = [
        'licitacao_id',
        'fornecedor_id',
        'valor',
        'status',
        'observacoes',
    ];

    public function licitacao()
    {
        return $this->belongsTo(\App\Models\Licitacao::class);
    }

    public function fornecedor()
    {
        return $this->belongsTo(\App\Models\Fornecedor::class);
    }
}
