<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'plan_id',
        'numero',
        'titulo',
        'tipo_contrato',
        'valor_implantacao',
        'valor_mensal',
        'valor_modulos_extras',
        'valor_total_mensal',
        'data_inicio',
        'data_fim',
        'dia_vencimento',
        'status',
        'link_proposta',
        'link_contrato',
        'observacoes',
    ];

    protected $casts = [
        'valor_implantacao' => 'decimal:2',
        'valor_mensal' => 'decimal:2',
        'valor_modulos_extras' => 'decimal:2',
        'valor_total_mensal' => 'decimal:2',
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'dia_vencimento' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
