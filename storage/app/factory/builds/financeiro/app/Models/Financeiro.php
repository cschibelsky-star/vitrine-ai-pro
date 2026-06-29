<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Financeiro extends Model
{
    use SoftDeletes;

    protected $table = 'financeiro';

    protected $fillable = [
        'cliente_id',
        'descricao',
        'valor',
        'vencimento',
        'status',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class);
    }
}
