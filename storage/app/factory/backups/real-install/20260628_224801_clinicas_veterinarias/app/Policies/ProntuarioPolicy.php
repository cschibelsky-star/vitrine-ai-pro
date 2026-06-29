<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Prontuario;

class ProntuarioPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Prontuario $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Prontuario $record): bool { return true; }
    public function delete(User $user, Prontuario $record): bool { return true; }
    public function restore(User $user, Prontuario $record): bool { return true; }
    public function forceDelete(User $user, Prontuario $record): bool { return true; }
}
