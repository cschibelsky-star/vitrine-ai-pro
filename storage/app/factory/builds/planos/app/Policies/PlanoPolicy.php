<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Plano;

class PlanoPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Plano $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Plano $record): bool { return true; }
    public function delete(User $user, Plano $record): bool { return true; }
}
