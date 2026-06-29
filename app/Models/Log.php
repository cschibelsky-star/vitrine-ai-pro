<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = ['usuario_id', 'acao', 'modulo', 'descricao', 'ip'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
