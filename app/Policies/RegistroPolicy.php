<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Registro;

class RegistroPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Registro $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Registro $record): bool { return true; }
    public function delete(User $user, Registro $record): bool { return true; }
    public function restore(User $user, Registro $record): bool { return true; }
    public function forceDelete(User $user, Registro $record): bool { return true; }
}
