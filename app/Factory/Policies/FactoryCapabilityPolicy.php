<?php
declare(strict_types=1);
namespace App\Factory\Policies;
use App\Factory\Models\FactoryCapability; use App\Factory\Policies\Concerns\ChecksFactoryPermissions;
class FactoryCapabilityPolicy { use ChecksFactoryPermissions; public function viewAny($user): bool { return $this->allowed($user,'factory.capabilities.view') || $this->allowed($user,'factory.access'); } public function view($user,FactoryCapability $record): bool { return $this->viewAny($user); } public function create($user): bool { return $this->allowed($user,'factory.capabilities.manage') || $this->allowed($user,'factory.manage'); } public function update($user,FactoryCapability $record): bool { return $this->create($user); } public function delete($user,FactoryCapability $record): bool { return $this->create($user); } }
