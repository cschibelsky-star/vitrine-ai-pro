<?php
declare(strict_types=1);
namespace App\Factory\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\SoftDeletes;
class FactoryCapability extends Model { use HasFactory, SoftDeletes; protected $table='factory_capabilities'; protected $fillable=['uuid','factory_project_id','name','slug','code','description','type','status','configuration','metadata','created_by','updated_by']; protected $casts=['configuration'=>'array','metadata'=>'array']; public function project(): BelongsTo { return $this->belongsTo(FactoryProject::class,'factory_project_id'); } public function blueprints(): HasMany { return $this->hasMany(FactoryBlueprint::class,'factory_capability_id'); } public function executions(): HasMany { return $this->hasMany(FactoryExecution::class,'factory_capability_id'); } }
