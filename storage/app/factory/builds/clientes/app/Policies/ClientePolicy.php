<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Cliente;

class ClientePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Cliente $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Cliente $record): bool { return true; }
    public function delete(User $user, Cliente $record): bool { return true; }
}
