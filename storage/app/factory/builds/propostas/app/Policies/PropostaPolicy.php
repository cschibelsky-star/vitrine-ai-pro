<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Proposta;

class PropostaPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Proposta $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Proposta $record): bool { return true; }
    public function delete(User $user, Proposta $record): bool { return true; }
}
