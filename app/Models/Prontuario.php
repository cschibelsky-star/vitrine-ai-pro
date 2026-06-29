<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prontuario extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'animal_id',
        'descricao',
        'diagnostico',
        'status'
    ];

    public function animal()
    {
        return $this->belongsTo(\App\Models\Animal::class);
    }
}
