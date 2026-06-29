<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Contrato;

class ContratoPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Contrato $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Contrato $record): bool { return true; }
    public function delete(User $user, Contrato $record): bool { return true; }
}
