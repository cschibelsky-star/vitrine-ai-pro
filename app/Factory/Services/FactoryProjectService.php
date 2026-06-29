<?php
declare(strict_types=1);
namespace App\Factory\Services;
use App\Factory\Models\FactoryProject;
class FactoryProjectService { public function all() { return FactoryProject::query()->latest()->get(); } }
