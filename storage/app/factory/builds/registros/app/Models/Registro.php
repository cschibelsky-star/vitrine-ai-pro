<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registro extends Model
{
    use SoftDeletes;

    protected $table = 'registros';

    protected $fillable = [
        'nome',
        'descricao',
        'status',
    ];

}
