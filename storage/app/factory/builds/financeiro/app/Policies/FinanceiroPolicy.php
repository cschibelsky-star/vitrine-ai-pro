<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Financeiro;

class FinanceiroPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Financeiro $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Financeiro $record): bool { return true; }
    public function delete(User $user, Financeiro $record): bool { return true; }
}
