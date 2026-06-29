<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Fornecedor;

class FornecedorPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Fornecedor $record): bool { return true; }
    public function create(User $user): bool { return true; }
    public function update(User $user, Fornecedor $record): bool { return true; }
    public function delete(User $user, Fornecedor $record): bool { return true; }
}
