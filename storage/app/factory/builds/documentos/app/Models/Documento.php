<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documento extends Model
{
    use SoftDeletes;

    protected $table = 'documentos';

    protected $fillable = [
        'registro_id',
        'nome',
        'arquivo',
        'status',
    ];

    public function registro()
    {
        return $this->belongsTo(\App\Models\Registro::class);
    }
}
