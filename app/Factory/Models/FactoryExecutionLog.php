<?php
declare(strict_types=1);
namespace App\Factory\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany; 
class FactoryExecutionLog extends Model { use HasFactory; protected $table='factory_execution_logs'; protected $fillable=['uuid','factory_execution_id','level','event','message','payload','created_by']; protected $casts=['payload'=>'array']; public function execution(): BelongsTo { return $this->belongsTo(FactoryExecution::class,'factory_execution_id'); } }
