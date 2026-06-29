<?php
declare(strict_types=1);
namespace App\Factory\Services;
use App\Factory\Models\FactoryBlueprint;
class FactoryBlueprintService { public function all() { return FactoryBlueprint::query()->latest()->get(); } }
