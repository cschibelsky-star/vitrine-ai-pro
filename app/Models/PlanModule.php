<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'module_id',
        'tipo_inclusao',
        'valor_adicional',
        'limite_uso',
        'observacoes',
        'status',
    ];

    protected $casts = [
        'valor_adicional' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
