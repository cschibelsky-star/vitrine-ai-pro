<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Licitaco extends Model
{
    use SoftDeletes;

    protected $table = 'licitacoes';

    protected $fillable = [
        'numero',
        'objeto',
        'modalidade',
        'data_abertura',
        'valor_estimado',
        'status',
    ];

}
