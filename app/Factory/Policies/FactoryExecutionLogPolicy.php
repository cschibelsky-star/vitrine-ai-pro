<?php
declare(strict_types=1);
namespace App\Factory\Policies;
use App\Factory\Models\FactoryExecutionLog; use App\Factory\Policies\Concerns\ChecksFactoryPermissions;
class FactoryExecutionLogPolicy { use ChecksFactoryPermissions; public function viewAny($user): bool { return $this->allowed($user,'factory.logs.view') || $this->allowed($user,'factory.access'); } public function view($user,FactoryExecutionLog $record): bool { return $this->viewAny($user); } public function create($user): bool { return $this->allowed($user,'factory.logs.manage') || $this->allowed($user,'factory.manage'); } public function update($user,FactoryExecutionLog $record): bool { return $this->create($user); } public function delete($user,FactoryExecutionLog $record): bool { return $this->create($user); } }
