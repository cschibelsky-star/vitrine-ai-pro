<?php
declare(strict_types=1);
namespace App\Factory\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo; use Illuminate\Database\Eloquent\Relations\HasMany; use Illuminate\Database\Eloquent\SoftDeletes;
class FactoryProject extends Model { use HasFactory, SoftDeletes; protected $table='factory_projects'; protected $fillable=['uuid','name','slug','code','description','status','metadata','created_by','updated_by']; protected $casts=['metadata'=>'array']; public function capabilities(): HasMany { return $this->hasMany(FactoryCapability::class,'factory_project_id'); } public function blueprints(): HasMany { return $this->hasMany(FactoryBlueprint::class,'factory_project_id'); } public function executions(): HasMany { return $this->hasMany(FactoryExecution::class,'factory_project_id'); } }
