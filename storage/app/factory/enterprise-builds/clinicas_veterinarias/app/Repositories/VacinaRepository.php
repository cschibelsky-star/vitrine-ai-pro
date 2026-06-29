<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Vacina;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class VacinaRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Vacina::query()->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Vacina::query()->latest()->get();
    }

    public function find(int|string $id): ?Vacina
    {
        return Vacina::query()->find($id);
    }

    public function create(array $data): Vacina
    {
        return Vacina::query()->create($data);
    }

    public function update(Vacina $record, array $data): Vacina
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(Vacina $record): bool
    {
        return (bool) $record->delete();
    }
}
