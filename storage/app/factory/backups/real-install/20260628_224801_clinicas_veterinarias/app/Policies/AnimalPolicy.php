<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Animal;

class AnimalPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Animal $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Animal $record): bool { return true; }
    public function delete(User $user, Animal $record): bool { return true; }
    public function restore(User $user, Animal $record): bool { return true; }
    public function forceDelete(User $user, Animal $record): bool { return true; }
}
