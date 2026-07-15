<?php

namespace App\Core\Catalog\Models;

use App\Models\CompanyModule;
use App\Models\PlanModule;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $table = 'modules';

    protected $fillable = [
        'product_id',
        'nome',
        'codigo',
        'descricao',
        'categoria',
        'tipo',
        'valor_adicional',
        'status',
        'ordem',
    ];

    protected $casts = [
        'valor_adicional' => 'decimal:2',
        'ordem' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function planModules(): HasMany
    {
        return $this->hasMany(PlanModule::class);
    }

    public function companyModules(): HasMany
    {
        return $this->hasMany(CompanyModule::class);
    }
}
