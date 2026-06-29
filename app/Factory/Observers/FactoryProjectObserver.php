<?php
declare(strict_types=1);
namespace App\Factory\Observers;
use App\Factory\Models\FactoryProject; use Illuminate\Support\Str;
class FactoryProjectObserver { public function creating(FactoryProject $model): void { $model->uuid ??= (string) Str::uuid(); if (in_array('slug', $model->getFillable(), true) && ! $model->slug && ($model->name ?? null)) { $model->slug = Str::slug($model->name); } if (in_array('code', $model->getFillable(), true) && ! $model->code && ($model->name ?? null)) { $model->code = strtoupper(Str::slug($model->name, '_')); } if (auth()->check()) { $model->created_by ??= auth()->id(); $model->updated_by ??= auth()->id(); } } public function updating(FactoryProject $model): void { if (auth()->check()) $model->updated_by = auth()->id(); } }
