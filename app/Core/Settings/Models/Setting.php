<?php

declare(strict_types=1);

namespace App\Core\Settings\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = ['empresa', 'logo', 'telefone', 'email', 'endereco'];
}
