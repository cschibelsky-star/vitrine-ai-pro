<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vacina;

class VacinaPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Vacina $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Vacina $record): bool { return true; }
    public function delete(User $user, Vacina $record): bool { return true; }
}
