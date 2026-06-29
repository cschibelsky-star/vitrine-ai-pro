<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Documento;

class DocumentoPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Documento $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Documento $record): bool { return true; }
    public function delete(User $user, Documento $record): bool { return true; }
    public function restore(User $user, Documento $record): bool { return true; }
    public function forceDelete(User $user, Documento $record): bool { return true; }
}
