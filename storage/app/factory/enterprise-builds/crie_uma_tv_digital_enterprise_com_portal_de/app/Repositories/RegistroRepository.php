<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Registro;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RegistroRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Registro::query()->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Registro::query()->latest()->get();
    }

    public function find(int|string $id): ?Registro
    {
        return Registro::query()->find($id);
    }

    public function create(array $data): Registro
    {
        return Registro::query()->create($data);
    }

    public function update(Registro $record, array $data): Registro
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(Registro $record): bool
    {
        return (bool) $record->delete();
    }
}
