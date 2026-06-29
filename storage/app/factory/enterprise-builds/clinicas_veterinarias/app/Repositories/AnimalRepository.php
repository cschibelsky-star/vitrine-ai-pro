<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Animal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AnimalRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Animal::query()->latest()->paginate($perPage);
    }

    public function all(): Collection
    {
        return Animal::query()->latest()->get();
    }

    public function find(int|string $id): ?Animal
    {
        return Animal::query()->find($id);
    }

    public function create(array $data): Animal
    {
        return Animal::query()->create($data);
    }

    public function update(Animal $record, array $data): Animal
    {
        $record->update($data);

        return $record->refresh();
    }

    public function delete(Animal $record): bool
    {
        return (bool) $record->delete();
    }
}
