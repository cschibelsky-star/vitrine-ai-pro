<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'registro_id',
        'nome',
        'arquivo',
        'status'
    ];

    public function registro()
    {
        return $this->belongsTo(\App\Models\Registro::class);
    }
}
