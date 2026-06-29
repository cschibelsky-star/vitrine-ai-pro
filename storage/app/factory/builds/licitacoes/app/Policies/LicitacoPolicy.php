<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Licitaco;

class LicitacoPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Licitaco $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Licitaco $record): bool { return true; }
    public function delete(User $user, Licitaco $record): bool { return true; }
}
