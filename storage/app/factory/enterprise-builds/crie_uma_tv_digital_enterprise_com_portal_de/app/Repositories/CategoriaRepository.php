<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Categoria;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoriaRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Categoria::query()->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Categoria::query()->latest()->get();
    }

    public function find(int|string $id): ?Categoria
    {
        return Categoria::query()->find($id);
    }

    public function create(array $data): Categoria
    {
        return Categoria::query()->create($data);
    }

    public function update(Categoria $record, array $data): Categoria
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(Categoria $record): bool
    {
        return (bool) $record->delete();
    }
}
