<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Financeiro extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'cliente_id',
        'descricao',
        'valor',
        'vencimento',
        'status'
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Cliente::class);
    }
}
