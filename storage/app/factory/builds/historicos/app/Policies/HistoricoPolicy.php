<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Historico;

class HistoricoPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Historico $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Historico $record): bool { return true; }
    public function delete(User $user, Historico $record): bool { return true; }
}
