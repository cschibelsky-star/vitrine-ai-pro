<?php
declare(strict_types=1);
namespace App\Factory\Policies;
use App\Factory\Models\FactoryBlueprint; use App\Factory\Policies\Concerns\ChecksFactoryPermissions;
class FactoryBlueprintPolicy { use ChecksFactoryPermissions; public function viewAny($user): bool { return $this->allowed($user,'factory.blueprints.view') || $this->allowed($user,'factory.access'); } public function view($user,FactoryBlueprint $record): bool { return $this->viewAny($user); } public function create($user): bool { return $this->allowed($user,'factory.blueprints.manage') || $this->allowed($user,'factory.manage'); } public function update($user,FactoryBlueprint $record): bool { return $this->create($user); } public function delete($user,FactoryBlueprint $record): bool { return $this->create($user); } }
