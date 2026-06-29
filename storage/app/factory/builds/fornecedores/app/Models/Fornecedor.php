<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fornecedor extends Model
{
    use SoftDeletes;

    protected $table = 'fornecedores';

    protected $fillable = [
        'categoria_id',
        'nome',
        'documento',
        'email',
        'telefone',
        'cidade',
        'status',
    ];

    public function categoria()
    {
        return $this->belongsTo(\App\Models\Categoria::class);
    }
}
