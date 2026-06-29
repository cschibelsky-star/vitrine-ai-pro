<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Diagnostico;

class DiagnosticoPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Diagnostico $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Diagnostico $record): bool { return true; }
    public function delete(User $user, Diagnostico $record): bool { return true; }
}
