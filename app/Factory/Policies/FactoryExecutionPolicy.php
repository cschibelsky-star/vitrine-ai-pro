<?php
declare(strict_types=1);
namespace App\Factory\Policies;
use App\Factory\Models\FactoryExecution; use App\Factory\Policies\Concerns\ChecksFactoryPermissions;
class FactoryExecutionPolicy { use ChecksFactoryPermissions; public function viewAny($user): bool { return $this->allowed($user,'factory.executions.view') || $this->allowed($user,'factory.access'); } public function view($user,FactoryExecution $record): bool { return $this->viewAny($user); } public function create($user): bool { return $this->allowed($user,'factory.executions.manage') || $this->allowed($user,'factory.manage'); } public function update($user,FactoryExecution $record): bool { return $this->create($user); } public function delete($user,FactoryExecution $record): bool { return $this->create($user); } }
