<?php
declare(strict_types=1);
namespace App\Factory\Services;
use App\Factory\Models\FactoryCapability;
class FactoryCapabilityService { public function all() { return FactoryCapability::query()->latest()->get(); } }
