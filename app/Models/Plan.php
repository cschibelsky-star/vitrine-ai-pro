<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['product_id','nome','valor_mensal','valor_implantacao','ciclo_cobranca','descricao','recursos','status'];

    protected $casts = ['valor_mensal'=>'decimal:2','valor_implantacao'=>'decimal:2'];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function licenses(): HasMany { return $this->hasMany(License::class); }
    public function planModules(): HasMany { return $this->hasMany(PlanModule::class); }
    public function contracts(): HasMany { return $this->hasMany(Contract::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }

    public function calcularVencimento(?Carbon $inicio = null): ?Carbon
    {
        $inicio = $inicio ?? now();
        return match ($this->ciclo_cobranca) { 'mensal'=>$inicio->copy()->addMonth(), 'anual'=>$inicio->copy()->addYear(), 'implantacao'=>null, 'trial'=>$inicio->copy()->addDays(30), 'cortesia'=>null, default=>$inicio->copy()->addMonth() };
    }

    public function statusLicencaSugerido(): string
    {
        return match ($this->ciclo_cobranca) { 'trial'=>'Trial', 'cortesia'=>'Homologação', 'implantacao'=>'Homologação', 'mensal'=>'Ativa', 'anual'=>'Ativa', default=>'Ativa' };
    }
}
