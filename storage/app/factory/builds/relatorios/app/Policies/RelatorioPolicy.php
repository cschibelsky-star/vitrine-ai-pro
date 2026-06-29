<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Relatorio;

class RelatorioPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Relatorio $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Relatorio $record): bool { return true; }
    public function delete(User $user, Relatorio $record): bool { return true; }
}
