<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClienteRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Cliente::query()->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Cliente::query()->latest()->get();
    }

    public function find(int|string $id): ?Cliente
    {
        return Cliente::query()->find($id);
    }

    public function create(array $data): Cliente
    {
        return Cliente::query()->create($data);
    }

    public function update(Cliente $record, array $data): Cliente
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(Cliente $record): bool
    {
        return (bool) $record->delete();
    }
}
