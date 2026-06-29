<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Animai;

class AnimaiPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Animai $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Animai $record): bool { return true; }
    public function delete(User $user, Animai $record): bool { return true; }
}
