<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'plan_id',
        'contract_id',
        'tipo_cobranca',
        'descricao',
        'valor',
        'competencia',
        'vencimento',
        'data_pagamento',
        'forma_pagamento',
        'status',
        'link_pagamento',
        'referencia_externa',
        'asaas_id',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'competencia' => 'date',
            'vencimento' => 'date',
            'data_pagamento' => 'date',
            'valor' => 'decimal:2',
        ];
    }

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

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
