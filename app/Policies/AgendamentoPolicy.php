<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Agendamento;

class AgendamentoPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Agendamento $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Agendamento $record): bool { return true; }
    public function delete(User $user, Agendamento $record): bool { return true; }
    public function restore(User $user, Agendamento $record): bool { return true; }
    public function forceDelete(User $user, Agendamento $record): bool { return true; }
}
