<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agendamento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'animal_id',
        'data_agendamento',
        'tipo',
        'observacoes',
        'status'
    ];

    public function animal()
    {
        return $this->belongsTo(\App\Models\Animal::class);
    }
}
