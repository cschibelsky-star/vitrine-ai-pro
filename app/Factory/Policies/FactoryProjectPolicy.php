<?php
declare(strict_types=1);
namespace App\Factory\Policies;
use App\Factory\Models\FactoryProject; use App\Factory\Policies\Concerns\ChecksFactoryPermissions;
class FactoryProjectPolicy { use ChecksFactoryPermissions; public function viewAny($user): bool { return $this->allowed($user,'factory.projects.view') || $this->allowed($user,'factory.access'); } public function view($user,FactoryProject $record): bool { return $this->viewAny($user); } public function create($user): bool { return $this->allowed($user,'factory.projects.manage') || $this->allowed($user,'factory.manage'); } public function update($user,FactoryProject $record): bool { return $this->create($user); } public function delete($user,FactoryProject $record): bool { return $this->create($user); } }
